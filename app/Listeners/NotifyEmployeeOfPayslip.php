<?php

namespace App\Listeners;

use App\Events\PayslipAvailable;
use App\Notifications\PayslipAvailableNotification;

class NotifyEmployeeOfPayslip
{
    public function handle(PayslipAvailable $event): void
    {
        $row = $event->payrollRow;
        $employee = $event->employee;
        $user = $employee->user;

        if (!$user) {
            return;
        }

        $user->notify(new PayslipAvailableNotification($row));
    }
}
