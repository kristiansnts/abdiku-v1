<?php

declare(strict_types=1);

namespace App\Domain\Payroll\ValueObjects;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Payroll\Models\PayrollRow;

final readonly class PayslipData
{
    public function __construct(
        // Company
        public string $companyName,
        public ?string $companyAddress,
        public ?string $companyPhone,
        public ?string $companyEmail,
        public ?string $companyLogoPath,
        public ?string $companyNpwp,

        // Period
        public ?int $year,
        public ?int $month,
        public string $monthName,
        public string $periodStart,
        public string $periodEnd,

        // Employee
        public int $employeeId,
        public string $employeeName,

        // Salary
        public float $baseSalary,
        public float $proratedBaseSalary,
        public array $allowances,
        public float $totalAllowances,

        // Attendance
        public int $totalWorkingDays,
        public int $payableDays,
        public array $attendanceBreakdown,

        // Earnings
        public array $additions,
        public float $totalAdditions,

        // Deductions
        public array $deductions,
        public float $totalEmployeeDeductions,
        public float $totalEmployerDeductions,

        // Summary
        public float $grossAmount,
        public float $totalDeductions,
        public float $taxAmount,
        public float $netAmount,

        // Metadata
        public ?string $finalizedAt,
    ) {}

    public static function fromPayrollRow(PayrollRow $payrollRow): self
    {
        $batch = $payrollRow->payrollBatch;
        $period = $batch->payrollPeriod;
        $company = $batch->company;
        $employee = $payrollRow->employee;

        // Get compensation data
        $compensation = $employee->compensations()
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first();

        $baseSalary = $compensation ? (float) $compensation->base_salary : 0;
        $allowances = [];
        $totalAllowances = 0;

        if ($compensation && is_array($compensation->allowances)) {
            foreach ($compensation->allowances as $name => $amount) {
                $allowances[] = [
                    'name' => $name,
                    'amount' => (float) $amount,
                ];
                $totalAllowances += (float) $amount;
            }
        }

        // Calculate attendance data
        $attendanceData = self::calculateAttendanceData($period, $payrollRow->employee_id);
        $totalWorkingDays = self::calculateTotalWorkingDays($period);

        // Calculate prorated salary
        $proratedBaseSalary = $totalWorkingDays > 0
            ? ($attendanceData['payable_days'] / $totalWorkingDays) * $baseSalary
            : $baseSalary;

        // Format additions
        $additions = $payrollRow->additions->map(fn ($addition) => [
            'code' => $addition->addition_code,
            'name' => $addition->description ?? $addition->addition_code,
            'amount' => (float) $addition->amount,
        ])->toArray();

        // Format deductions with rule snapshots
        $deductions = $payrollRow->deductions->map(function ($deduction) {
            $snapshot = $deduction->rule_snapshot ?? [];

            return [
                'code' => $deduction->deduction_code,
                'name' => $snapshot['name'] ?? $deduction->deduction_code,
                'employee_amount' => (float) $deduction->employee_amount,
                'employer_amount' => (float) $deduction->employer_amount,
                'rate' => isset($snapshot['employee_rate']) ? (float) $snapshot['employee_rate'] : null,
                'basis' => $snapshot['basis_type'] ?? null,
            ];
        })->toArray();

        return new self(
            companyName: $company->name,
            companyAddress: $company->address,
            companyPhone: $company->phone,
            companyEmail: $company->email,
            companyLogoPath: $company->logo_path,
            companyNpwp: $company->npwp,
            year: $period->year,
            month: $period->month,
            monthName: self::getMonthName($period->month),
            periodStart: $period->period_start?->format('d M Y') ?? '',
            periodEnd: $period->period_end?->format('d M Y') ?? '',
            employeeId: $employee->id,
            employeeName: $employee->name,
            baseSalary: $baseSalary,
            proratedBaseSalary: round($proratedBaseSalary, 2),
            allowances: $allowances,
            totalAllowances: $totalAllowances,
            totalWorkingDays: $totalWorkingDays,
            payableDays: $attendanceData['payable_days'],
            attendanceBreakdown: $attendanceData['breakdown'],
            additions: $additions,
            totalAdditions: (float) $payrollRow->additions->sum('amount'),
            deductions: $deductions,
            totalEmployeeDeductions: $payrollRow->total_employee_deductions,
            totalEmployerDeductions: $payrollRow->total_employer_deductions,
            grossAmount: (float) $payrollRow->gross_amount,
            totalDeductions: (float) $payrollRow->deduction_amount,
            taxAmount: (float) $payrollRow->tax_amount,
            netAmount: (float) $payrollRow->net_amount,
            finalizedAt: $batch->finalized_at?->format('d M Y H:i'),
        );
    }

    private static function calculateAttendanceData($period, int $employeeId): array
    {
        $decisions = $period->attendanceDecisions()
            ->where('employee_id', $employeeId)
            ->get();

        $breakdown = [
            'hadir' => 0,
            'terlambat' => 0,
            'cuti_dibayar' => 0,
            'cuti_tidak_dibayar' => 0,
            'sakit_dibayar' => 0,
            'sakit_tidak_dibayar' => 0,
            'libur_dibayar' => 0,
            'libur_tidak_dibayar' => 0,
            'absen' => 0,
        ];

        foreach ($decisions as $decision) {
            match ($decision->classification) {
                AttendanceClassification::ATTEND => $breakdown['hadir']++,
                AttendanceClassification::LATE => $breakdown['terlambat']++,
                AttendanceClassification::PAID_LEAVE => $breakdown['cuti_dibayar']++,
                AttendanceClassification::UNPAID_LEAVE => $breakdown['cuti_tidak_dibayar']++,
                AttendanceClassification::PAID_SICK => $breakdown['sakit_dibayar']++,
                AttendanceClassification::UNPAID_SICK => $breakdown['sakit_tidak_dibayar']++,
                AttendanceClassification::HOLIDAY_PAID => $breakdown['libur_dibayar']++,
                AttendanceClassification::HOLIDAY_UNPAID => $breakdown['libur_tidak_dibayar']++,
                AttendanceClassification::ABSENT => $breakdown['absen']++,
            };
        }

        return [
            'payable_days' => $decisions->where('payable', true)->count(),
            'breakdown' => $breakdown,
        ];
    }

    private static function calculateTotalWorkingDays($period): int
    {
        $start = $period->period_start;
        $end = $period->period_end;

        if (! $start || ! $end) {
            return 0;
        }

        $totalWorkingDays = 0;
        $currentDate = $start->copy();

        while ($currentDate <= $end) {
            if ($currentDate->isWeekday()) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }

        return $totalWorkingDays;
    }

    private static function getMonthName(?int $month): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$month] ?? '';
    }
}
