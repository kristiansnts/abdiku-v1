<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\ValueObjects\AttendanceStatusResult;
use App\Models\Employee;

class AttendanceStatusService
{
    public function getTodayStatus(Employee $employee): AttendanceStatusResult
    {
        $today = now()->toDateString();

        $todayAttendance = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->latest()
            ->first();

        if (! $todayAttendance) {
            return new AttendanceStatusResult(
                canClockIn: true,
                canClockOut: false,
                hasClockedIn: false,
                hasClockedOut: false,
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
                message: 'Sudah clock in, silakan clock out',
            );
        }

        return new AttendanceStatusResult(
            canClockIn: true,
            canClockOut: false,
            hasClockedIn: false,
            hasClockedOut: false,
            todayAttendance: $todayAttendance,
            message: 'Silakan melakukan clock in',
        );
    }
}
