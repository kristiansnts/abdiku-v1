<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObjects;

use App\Domain\Attendance\Models\AttendanceRaw;

readonly class AttendanceStatusResult
{
    public function __construct(
        public bool $canClockIn,
        public bool $canClockOut,
        public bool $hasClockedIn,
        public bool $hasClockedOut,
        public ?AttendanceRaw $todayAttendance = null,
        public ?string $message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'can_clock_in' => $this->canClockIn,
            'can_clock_out' => $this->canClockOut,
            'has_clocked_in' => $this->hasClockedIn,
            'has_clocked_out' => $this->hasClockedOut,
            'today_attendance' => $this->todayAttendance ? [
                'id' => $this->todayAttendance->id,
                'date' => $this->todayAttendance->date->format('Y-m-d'),
                'clock_in' => $this->todayAttendance->clock_in?->format('H:i:s'),
                'clock_out' => $this->todayAttendance->clock_out?->format('H:i:s'),
                'status' => $this->todayAttendance->status->value,
            ] : null,
            'message' => $this->message,
        ];
    }
}
