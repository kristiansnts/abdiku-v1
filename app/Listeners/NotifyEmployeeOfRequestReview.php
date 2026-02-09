<?php

namespace App\Listeners;

use App\Events\AttendanceRequestReviewed;
use App\Notifications\AttendanceRequestReviewedNotification;

class NotifyEmployeeOfRequestReview
{
    public function handle(AttendanceRequestReviewed $event): void
    {
        $request = $event->attendanceRequest;
        $user = $request->employee->user;

        if (!$user) {
            return;
        }

        $user->notify(new AttendanceRequestReviewedNotification(
            $request,
            $event->approved,
            $event->reviewer
        ));
    }
}
