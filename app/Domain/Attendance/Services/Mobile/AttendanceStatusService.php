<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\ValueObjects\AttendanceStatusResult;
use App\Domain\Leave\Models\Holiday;
use App\Models\Employee;

class AttendanceStatusService
{
    public function getTodayStatus(Employee $employee): AttendanceStatusResult
    {
        $today = now()->toDateString();

        // Check for holiday first
        $holiday = Holiday::where('company_id', $employee->company_id)
            ->where('date', $today)
            ->first();

        // Get current shift policy from active work assignment
        $workAssignment = $employee->getWorkAssignmentOn(now());
        $shiftPolicy = $workAssignment?->shiftPolicy;

        // If today is a holiday, block clock-in
        if ($holiday !== null) {
            return new AttendanceStatusResult(
                canClockIn: false,
                canClockOut: false,
                hasClockedIn: false,
                hasClockedOut: false,
                shiftPolicy: $shiftPolicy,
                message: "Hari ini adalah hari libur: {$holiday->name}",
                isHoliday: true,
                holiday: $holiday,
            );
        }

        $todayAttendance = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->with('employee')
            ->latest()
            ->first();

        if (! $todayAttendance) {
            return new AttendanceStatusResult(
                canClockIn: true,
                canClockOut: false,
                hasClockedIn: false,
                hasClockedOut: false,
                shiftPolicy: $shiftPolicy,
                message: 'Belum melakukan clock in hari ini',
            );
        }

        $hasClockedIn = $todayAttendance->clock_in !== null;
        $hasClockedOut = $todayAttendance->clock_out !== null;

        if ($hasClockedIn && $hasClockedOut) {
            return new AttendanceStatusResult(
                canClockIn: false,
                canClockOut: false,
                hasClockedIn: true,
                hasClockedOut: true,
                todayAttendance: $todayAttendance,
                shiftPolicy: $shiftPolicy,
                message: 'Sudah melakukan clock in dan clock out hari ini',
            );
        }

        if ($hasClockedIn && ! $hasClockedOut) {
            return new AttendanceStatusResult(
                canClockIn: false,
                canClockOut: true,
                hasClockedIn: true,
                hasClockedOut: false,
                todayAttendance: $todayAttendance,
                shiftPolicy: $shiftPolicy,
                message: 'Sudah clock in, silakan clock out',
            );
        }

        return new AttendanceStatusResult(
            canClockIn: true,
            canClockOut: false,
            hasClockedIn: false,
            hasClockedOut: false,
            todayAttendance: $todayAttendance,
            shiftPolicy: $shiftPolicy,
            message: 'Silakan melakukan clock in',
        );
    }
}
