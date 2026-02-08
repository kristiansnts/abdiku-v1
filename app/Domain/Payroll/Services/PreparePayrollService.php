<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceTimeCorrection;
use App\Domain\Leave\Models\Holiday;
use App\Domain\Leave\Models\LeaveRecord;
use App\Domain\Payroll\Enums\DeductionType;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Events\EmployeeAbsentDetected;
use App\Events\PayrollPrepared;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class PreparePayrollService
{
    public function execute(PayrollPeriod $period, User $actor): void
    {
        $this->validateState($period);
        $this->validateRole($actor);

        DB::transaction(function () use ($period, $actor) {
            $this->generateDecisions($period);
            $this->lockAttendanceRecords($period);

            // Dispatch event for notification
            $employeeCount = Employee::where('company_id', $period->company_id)->count();
            event(new PayrollPrepared($period, $actor, $employeeCount));
        });
    }

    private function validateState(PayrollPeriod $period): void
    {
        if ($period->state !== PayrollState::DRAFT) {
            throw new InvalidPayrollStateException(
                currentState: $period->state,
                requiredState: PayrollState::DRAFT,
            );
        }
    }

    private function validateRole(User $actor): void
    {
        if (!$actor->hasRole('hr') && !$actor->hasRole('owner')) {
            throw new UnauthorizedPayrollActionException(
                action: 'prepare payroll',
                requiredRole: 'hr',
            );
        }
    }

    private function generateDecisions(PayrollPeriod $period): void
    {
        $employees = Employee::where('company_id', $period->company_id)->get();
        $dates = CarbonPeriod::create($period->period_start, $period->period_end);

        foreach ($employees as $employee) {
            foreach ($dates as $date) {
                $this->classifyDay($period, $employee, $date->toDateString());
            }
        }
    }

    private function classifyDay(PayrollPeriod $period, Employee $employee, string $date): void
    {
        $leave = LeaveRecord::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        $holiday = Holiday::where('company_id', $employee->company_id)
            ->where('date', $date)
            ->first();

        $attendance = AttendanceRaw::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        // Check for time correction (from employee request or HR correction)
        $correction = AttendanceTimeCorrection::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        $classification = $this->determineClassification($attendance, $leave, $holiday, $correction);
        $deduction = $this->determineDeduction($classification);
        AttendanceDecision::updateOrCreate(
            [
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'date' => $date,
            ],
            [
                'classification' => $classification,
                'payable' => $this->isPayable($classification),
                'deduction_type' => $deduction['type'],
                'deduction_value' => $deduction['value'],
                'rule_version' => $period->rule_version,
                'decided_at' => now(),
            ]
        );

        // Dispatch event for absence detection
        if ($classification === AttendanceClassification::ABSENT) {
            event(new EmployeeAbsentDetected($employee, $date, $employee->company));
        }
    }

    private function determineClassification(
        ?AttendanceRaw $attendance,
        ?LeaveRecord $leave,
        ?Holiday $holiday,
        ?AttendanceTimeCorrection $correction = null,
    ): AttendanceClassification {
        // Leave takes precedence
        if ($leave !== null) {
            return match ($leave->leave_type->value) {
                'PAID' => AttendanceClassification::PAID_LEAVE,
                'UNPAID' => AttendanceClassification::UNPAID_LEAVE,
                'SICK_PAID' => AttendanceClassification::PAID_SICK,
                'SICK_UNPAID' => AttendanceClassification::UNPAID_SICK,
            };
        }

        // Holiday takes precedence over attendance
        if ($holiday !== null) {
            return $holiday->is_paid
                ? AttendanceClassification::HOLIDAY_PAID
                : AttendanceClassification::HOLIDAY_UNPAID;
        }

        // Use corrected clock-in if available, otherwise use raw clock-in
        $effectiveClockIn = $correction?->corrected_clock_in ?? $attendance?->clock_in;

        // No attendance record and no correction = ABSENT
        if ($attendance === null && $correction === null) {
            return AttendanceClassification::ABSENT;
        }

        // Have record but no effective clock-in = ABSENT
        if ($effectiveClockIn === null) {
            return AttendanceClassification::ABSENT;
        }

        return AttendanceClassification::ATTEND;
    }

    private function isPayable(AttendanceClassification $classification): bool
    {
        return in_array($classification, [
            AttendanceClassification::ATTEND,
            AttendanceClassification::LATE,
            AttendanceClassification::PAID_LEAVE,
            AttendanceClassification::PAID_SICK,
            AttendanceClassification::HOLIDAY_PAID,
        ], true);
    }

    /**
     * @return array{type: DeductionType, value: ?string}
     */
    private function determineDeduction(AttendanceClassification $classification): array
    {
        return match ($classification) {
            AttendanceClassification::ABSENT,
            AttendanceClassification::UNPAID_LEAVE,
            AttendanceClassification::UNPAID_SICK,
            AttendanceClassification::HOLIDAY_UNPAID => [
                'type' => DeductionType::FULL,
                'value' => null,
            ],
            default => [
                'type' => DeductionType::NONE,
                'value' => null,
            ],
        };
    }

    private function lockAttendanceRecords(PayrollPeriod $period): void
    {
        AttendanceRaw::where('company_id', $period->company_id)
            ->whereBetween('date', [$period->period_start, $period->period_end])
            ->whereIn('status', [AttendanceStatus::PENDING, AttendanceStatus::APPROVED])
            ->update(['status' => AttendanceStatus::LOCKED]);
    }
}
