<?php

namespace App\Listeners;

use App\Events\AttendanceRequestSubmitted;
use App\Helpers\NotificationRecipientHelper;
use App\Notifications\AttendanceRequestSubmittedNotification;


class NotifyHrOfAttendanceRequest
{
    public function handle(AttendanceRequestSubmitted $event): void
    {
        $request = $event->attendanceRequest;
        $hrUsers = NotificationRecipientHelper::getHrUsers($request->company_id);



        foreach ($hrUsers as $hrUser) {

            $hrUser->notify(new AttendanceRequestSubmittedNotification($request));
        }
    }
}
