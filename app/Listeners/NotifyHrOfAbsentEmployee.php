<?php

namespace App\Listeners;

use App\Events\EmployeeAbsentDetected;
use App\Helpers\NotificationRecipientHelper;
use App\Notifications\EmployeeAbsentNotification;

class NotifyHrOfAbsentEmployee
{
    public function handle(EmployeeAbsentDetected $event): void
    {
        $employee = $event->employee;
        $date = $event->date;
        $hrUsers = NotificationRecipientHelper::getHrUsers($event->company->id);

        foreach ($hrUsers as $hrUser) {
            $hrUser->notify(new EmployeeAbsentNotification($employee, $date));
        }
    }
}
