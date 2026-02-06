<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Attendance\Enums\AttendanceClassification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeePayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $period = $this->payrollBatch->payrollPeriod;
        $attendanceBreakdown = $this->getAttendanceBreakdown($period);
        $compensation = $this->getCompensationSnapshot();

        return [
            'id' => $this->id,
            'period' => [
                'year' => $period->year,
                'month' => $period->month,
                'month_name' => $period->month ? $this->getMonthName($period->month) : null,
                'period_start' => $period->period_start?->format('Y-m-d'),
                'period_end' => $period->period_end?->format('Y-m-d'),
            ],

            // Backward compatible fields (for existing mobile app)
            'gross_amount' => (float) $this->gross_amount,
            'deduction_amount' => (float) $this->deduction_amount,
            'net_amount' => (float) $this->net_amount,
            'attendance_count' => $this->attendance_count,
            'additions' => $this->additions->map(fn ($addition) => [
                'code' => $addition->addition_code,
                'description' => $addition->description,
                'amount' => (float) $addition->amount,
            ]),

            // New detailed fields
            'employee' => [
                'id' => $this->employee_id,
                'name' => $this->employee?->name,
            ],
            'salary' => [
                'base_salary' => $compensation['base_salary'],
                'prorated_base_salary' => $compensation['prorated_base_salary'],
                'allowances' => $compensation['allowances'],
                'total_allowances' => $compensation['total_allowances'],
            ],
            'attendance' => [
                'payable_days' => $attendanceBreakdown['payable_days'],
                'total_working_days' => $attendanceBreakdown['total_working_days'],
                'breakdown' => $attendanceBreakdown['breakdown'],
            ],
            'earnings' => [
                'salary' => $compensation['prorated_base_salary'],
                'allowances' => $compensation['total_allowances'],
                'additions' => $this->additions->map(fn ($addition) => [
                    'code' => $addition->addition_code,
                    'name' => $addition->description ?? $addition->addition_code,
                    'amount' => (float) $addition->amount,
                ]),
                'total_additions' => (float) $this->additions->sum('amount'),
            ],
            'deductions' => $this->deductions->map(function ($deduction) {
                $snapshot = $deduction->rule_snapshot ?? [];

                return [
                    'code' => $deduction->deduction_code,
                    'name' => $snapshot['name'] ?? $deduction->deduction_code,
                    'employee_amount' => (float) $deduction->employee_amount,
                    'employer_amount' => (float) $deduction->employer_amount,
                    'rate' => isset($snapshot['employee_rate']) ? (float) $snapshot['employee_rate'] : null,
                    'basis' => $snapshot['basis_type'] ?? null,
                ];
            }),
            'summary' => [
                'gross_amount' => (float) $this->gross_amount,
                'total_deductions' => (float) $this->deduction_amount,
                'net_amount' => (float) $this->net_amount,
            ],
            'finalized_at' => $this->payrollBatch->finalized_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function getCompensationSnapshot(): array
    {
        // Get current compensation as fallback (may differ from payroll time)
        $compensation = $this->employee?->compensations()
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first();

        $baseSalary = $compensation ? (float) $compensation->base_salary : 0;
        $allowances = $compensation?->allowances ?? [];
        $totalAllowances = $compensation ? (float) $compensation->total_allowances : 0;

        // Calculate prorated base salary
        $attendanceCount = $this->attendance_count;
        $totalWorkingDays = $this->getTotalWorkingDays();

        $proratedBaseSalary = $totalWorkingDays > 0
            ? ($attendanceCount / $totalWorkingDays) * $baseSalary
            : $baseSalary;

        // Format allowances as array of objects
        $formattedAllowances = [];
        if (is_array($allowances)) {
            foreach ($allowances as $name => $amount) {
                $formattedAllowances[] = [
                    'name' => $name,
                    'amount' => (float) $amount,
                ];
            }
        }

        return [
            'base_salary' => $baseSalary,
            'prorated_base_salary' => round($proratedBaseSalary, 2),
            'allowances' => $formattedAllowances,
            'total_allowances' => $totalAllowances,
        ];
    }

    private function getAttendanceBreakdown($period): array
    {
        $decisions = $period->attendanceDecisions()
            ->where('employee_id', $this->employee_id)
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

        $payableDays = $decisions->where('payable', true)->count();

        return [
            'payable_days' => $payableDays,
            'total_working_days' => $this->getTotalWorkingDays(),
            'breakdown' => $breakdown,
        ];
    }

    private function getTotalWorkingDays(): int
    {
        $period = $this->payrollBatch->payrollPeriod;
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

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $months[$month] ?? '';
    }
}
