<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Models\PayrollAddition;
use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollDeductionRule;
use App\Domain\Payroll\Models\PayrollRow;
use App\Domain\Payroll\Models\PayrollRowAddition;
use App\Domain\Payroll\Models\PayrollRowDeduction;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalculatePayrollService
{
    public function __construct(
        protected TaxCalculationService $taxService
    ) {}

    public function execute(PayrollBatch $batch): void
    {
        // Load the payroll period with company relationship
        $batch->load('payrollPeriod.company');
        $period = $batch->payrollPeriod;

        // Check if period exists
        if (!$period) {
            throw new \RuntimeException('PayrollBatch must have a valid PayrollPeriod');
        }

        // Check if company exists
        if (!$period->company) {
            throw new \RuntimeException('PayrollPeriod must be associated with a Company');
        }

        $employees = $period->company->employees()->where('status', 'ACTIVE')->get();

        DB::transaction(function () use ($batch, $period, $employees) {
            foreach ($employees as $employee) {
                $this->calculateForEmployee($batch, $period, $employee);
            }
        });
    }

    protected function calculateForEmployee(PayrollBatch $batch, $period, Employee $employee): void
    {
        // Get active compensation
        $compensation = $this->getActiveCompensation($employee);

        if (!$compensation) {
            // Skip employees without compensation
            return;
        }

        // Calculate attendance
        $attendanceData = $this->calculateAttendance($employee, $period);
        $attendanceCount = $attendanceData['payable_days'];
        $totalWorkingDays = $attendanceData['total_working_days'];

        // Calculate prorated base salary based on attendance
        $baseSalary = (float) $compensation->base_salary;
        if ($totalWorkingDays > 0) {
            $proratedBaseSalary = ($attendanceCount / $totalWorkingDays) * $baseSalary;
        } else {
            $proratedBaseSalary = $baseSalary;
        }

        // Calculate allowances based on type
        $fixedAllowances = $compensation->getFixedAllowancesSum();
        $variableAllowancesPerMonth = $compensation->getVariableAllowancesSum();
        
        // Variable allowances (like meal/transport) are paid based on attendance
        $proratedVariableAllowances = 0;
        if ($totalWorkingDays > 0) {
            $proratedVariableAllowances = ($attendanceCount / $totalWorkingDays) * $variableAllowancesPerMonth;
        }

        $totalAllowances = $fixedAllowances + $proratedVariableAllowances;

        // Get additions for this period
        $additions = $this->getAdditions($employee, $period);
        $totalAdditions = $additions->sum('amount');

        $grossAmount = $proratedBaseSalary + $totalAllowances + $totalAdditions;

        // Calculate deductions
        $deductions = $this->calculateDeductions($employee, $compensation, $grossAmount);
        $totalEmployeeDeductions = collect($deductions)->sum('employee_amount');

        // Calculate Tax (PPh21)
        $taxResult = $this->taxService->calculateMonthlyTax($employee, $grossAmount);
        $taxAmount = $taxResult['tax_amount'];

        // Calculate net (Gross - Deductions - Tax)
        $netAmount = $grossAmount - $totalEmployeeDeductions - $taxAmount;

        // Audit Trail: Transparansi Hitungan untuk Owner/Admin
        $auditLog = [
            'version' => 'Compliance-v1-TER2024',
            'timestamp' => now()->toDateTimeString(),
            'breakdown' => [
                [
                    'label' => 'Gaji Pokok Prorata',
                    'formula' => "({$attendanceCount}/{$totalWorkingDays}) * " . number_format($baseSalary),
                    'result' => $proratedBaseSalary,
                    'note' => 'Berdasarkan hari kerja efektif karyawan.'
                ],
                [
                    'label' => 'Tunjangan Variabel Prorata',
                    'formula' => "({$attendanceCount}/{$totalWorkingDays}) * " . number_format($variableAllowancesPerMonth),
                    'result' => $proratedVariableAllowances,
                    'note' => 'Makan/Transport dipotong jika absen (Zero-Leakage).'
                ],
                [
                    'label' => 'Kategori TER PPh21',
                    'formula' => $employee->ptkp_status,
                    'result' => $taxResult['category'],
                    'note' => 'Berdasarkan status PTKP terbaru (PP 58/2023).'
                ],
                [
                    'label' => 'Tarif Pajak Efektif',
                    'formula' => "Bruto " . number_format($grossAmount),
                    'result' => ($taxResult['rate'] * 100) . '%',
                    'note' => "Total Pajak: Rp " . number_format($taxAmount)
                ]
            ],
            'legal_references' => [
                'PP No. 58 Tahun 2023 (PPh21 TER)',
                'UU HPP No. 7 Tahun 2021',
                'Permenaker No. 6 Tahun 2016'
            ]
        ];

        // Create payroll row
        $row = PayrollRow::create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'gross_amount' => $grossAmount,
            'deduction_amount' => $totalEmployeeDeductions, 
            'tax_amount' => $taxAmount,
            'net_amount' => $netAmount,
            'audit_log' => $auditLog, // Inject transparency log
        ]);

        // Create deduction details
        foreach ($deductions as $deduction) {
            PayrollRowDeduction::create([
                'payroll_row_id' => $row->id,
                'deduction_code' => $deduction['code'],
                'employee_amount' => $deduction['employee_amount'],
                'employer_amount' => $deduction['employer_amount'],
                'rule_snapshot' => $deduction['snapshot'],
            ]);
        }

        // Create addition details
        foreach ($additions as $addition) {
            PayrollRowAddition::create([
                'payroll_row_id' => $row->id,
                'addition_code' => $addition->code,
                'amount' => $addition->amount,
                'source_reference' => $addition->id,
                'description' => $addition->description,
            ]);
        }
    }

    protected function getActiveCompensation(Employee $employee): ?EmployeeCompensation
    {
        return $employee->compensations()
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first();
    }

    protected function calculateAttendance(Employee $employee, $period): array
    {
        // Count payable attendance days
        $payableDays = $period->attendanceDecisions()
            ->where('employee_id', $employee->id)
            ->where('payable', true)
            ->count();

        // Get the work assignment active for this period
        $assignment = $employee->getWorkAssignmentOn($period->period_start);
        
        if ($assignment && $assignment->workPattern) {
            $totalWorkingDays = $assignment->workPattern->countWorkingDaysInRange(
                $period->period_start,
                $period->period_end
            );
        } else {
            // Fallback to standard 5-day week if no pattern is assigned
            $start = $period->period_start;
            $end = $period->period_end;
            $totalWorkingDays = 0;
            $currentDate = $start->copy();

            while ($currentDate <= $end) {
                if ($currentDate->isWeekday()) {
                    $totalWorkingDays++;
                }
                $currentDate->addDay();
            }
        }

        return [
            'payable_days' => $payableDays,
            'total_working_days' => $totalWorkingDays,
        ];
    }

    protected function getAdditions(Employee $employee, $period): Collection
    {
        return PayrollAddition::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->get();
    }

    protected function calculateDeductions(Employee $employee, EmployeeCompensation $compensation, float $grossAmount): array
    {
        $companyId = $employee->company_id;
        $deductions = [];

        // Get active deduction rules for company
        $rules = PayrollDeductionRule::query()
            ->where('company_id', $companyId)
            ->where('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            })
            ->get();

        foreach ($rules as $rule) {
            $basis = match ($rule->basis_type->value) {
                'BASE_SALARY' => (float) $compensation->base_salary,
                'CAPPED_SALARY' => min((float) $compensation->base_salary, (float) $rule->salary_cap ?? PHP_FLOAT_MAX),
                'GROSS_SALARY' => $grossAmount,
                default => (float) $compensation->base_salary,
            };

            $calculation = $rule->calculateDeduction($basis);

            $deductions[] = [
                'code' => $rule->code,
                'employee_amount' => $calculation['employee_amount'],
                'employer_amount' => $calculation['employer_amount'],
                'snapshot' => [
                    'rule_id' => $rule->id,
                    'code' => $rule->code,
                    'name' => $rule->name,
                    'basis_type' => $rule->basis_type->value,
                    'employee_rate' => (float) $rule->employee_rate,
                    'employer_rate' => (float) $rule->employer_rate,
                    'salary_cap' => $rule->salary_cap ? (float) $rule->salary_cap : null,
                    'calculation_basis' => $basis,
                    'employee_amount' => $calculation['employee_amount'],
                    'employer_amount' => $calculation['employer_amount'],
                ],
            ];
        }

        return $deductions;
    }
}
