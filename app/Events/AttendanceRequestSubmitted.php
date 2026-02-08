<?php

namespace App\Events;

use App\Domain\Attendance\Models\AttendanceRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRequestSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AttendanceRequest $attendanceRequest
    ) {
    }
}
