<?php

namespace App\Events;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRecordReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AttendanceRaw $attendanceRecord,
        public bool $approved,
        public User $reviewer
    ) {
    }
}
