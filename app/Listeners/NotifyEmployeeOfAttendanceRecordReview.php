<?php

namespace App\Listeners;

use App\Events\AttendanceRecordReviewed;
use App\Notifications\AttendanceRecordReviewedNotification;

class NotifyEmployeeOfAttendanceRecordReview
{
    public function handle(AttendanceRecordReviewed $event): void
    {
        $user = $event->attendanceRecord->employee?->user;

        if (!$user) {
            return;
        }

        $user->notify(new AttendanceRecordReviewedNotification(
            $event->attendanceRecord,
            $event->approved,
            $event->reviewer
        ));
    }
}
