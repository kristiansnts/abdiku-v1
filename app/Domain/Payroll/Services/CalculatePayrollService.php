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

        $allowances = $compensation->total_allowances;

        // Get additions for this period
        $additions = $this->getAdditions($employee, $period);
        $totalAdditions = $additions->sum('amount');

        $grossAmount = $proratedBaseSalary + $allowances + $totalAdditions;

        // Calculate deductions
        $deductions = $this->calculateDeductions($employee, $compensation, $grossAmount);
        $totalEmployeeDeductions = collect($deductions)->sum('employee_amount');

        // Calculate net
        $netAmount = $grossAmount - $totalEmployeeDeductions;

        // Create payroll row
        $row = PayrollRow::create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'gross_amount' => $grossAmount,
            'deduction_amount' => $totalEmployeeDeductions,
            'net_amount' => $netAmount,
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

        // Calculate total working days (excluding weekends)
        $start = $period->period_start;
        $end = $period->period_end;
        $totalWorkingDays = 0;
        $currentDate = $start->copy();

        while ($currentDate <= $end) {
            // Count only weekdays (Monday to Friday)
            if ($currentDate->isWeekday()) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
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
