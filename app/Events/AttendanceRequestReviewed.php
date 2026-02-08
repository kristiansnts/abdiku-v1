<?php

namespace App\Events;

use App\Domain\Attendance\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRequestReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AttendanceRequest $attendanceRequest,
        public bool $approved,
        public User $reviewer
    ) {
    }
}
