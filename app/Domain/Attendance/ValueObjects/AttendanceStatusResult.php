<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObjects;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\ShiftPolicy;
use App\Domain\Leave\Models\Holiday;

readonly class AttendanceStatusResult
{
    public function __construct(
        public bool $canClockIn,
        public bool $canClockOut,
        public bool $hasClockedIn,
        public bool $hasClockedOut,
        public ?AttendanceRaw $todayAttendance = null,
        public ?ShiftPolicy $shiftPolicy = null,
        public ?string $message = null,
        public bool $isHoliday = false,
        public ?Holiday $holiday = null,
    ) {}

    public function toArray(): array
    {
        $employeeTimezone = $this->todayAttendance?->employee->timezone ?? 'Asia/Jakarta';

        return [
            'can_clock_in' => $this->canClockIn,
            'can_clock_out' => $this->canClockOut,
            'has_clocked_in' => $this->hasClockedIn,
            'has_clocked_out' => $this->hasClockedOut,
            'today_attendance' => $this->todayAttendance ? [
                'id' => $this->todayAttendance->id,
                'date' => $this->todayAttendance->date->format('Y-m-d'),
                'clock_in' => $this->todayAttendance->clock_in?->setTimezone($employeeTimezone)->format('H:i:s'),
                'clock_out' => $this->todayAttendance->clock_out?->setTimezone($employeeTimezone)->format('H:i:s'),
                'status' => $this->todayAttendance->status->value,
            ] : null,
            'shift' => $this->shiftPolicy ? [
                'id' => $this->shiftPolicy->id,
                'name' => $this->shiftPolicy->name,
                'start_time' => $this->shiftPolicy->start_time?->format('H:i'),
                'end_time' => $this->shiftPolicy->end_time?->format('H:i'),
                'late_after_minutes' => $this->shiftPolicy->late_after_minutes,
            ] : null,
            'message' => $this->message,
            'is_holiday' => $this->isHoliday,
            'holiday' => $this->holiday ? [
                'id' => $this->holiday->id,
                'name' => $this->holiday->name,
                'date' => $this->holiday->date->format('Y-m-d'),
                'is_paid' => $this->holiday->is_paid,
            ] : null,
        ];
    }
}
