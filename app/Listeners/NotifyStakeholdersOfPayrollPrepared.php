<?php

namespace App\Listeners;

use App\Events\PayrollPrepared;
use App\Helpers\NotificationRecipientHelper;
use App\Notifications\PayrollPreparedNotification;

class NotifyStakeholdersOfPayrollPrepared
{
    public function handle(PayrollPrepared $event): void
    {
        $period = $event->payrollPeriod;
        $employeeCount = $event->employeeCount;
        $stakeholders = NotificationRecipientHelper::getStakeholders($period->company_id);

        foreach ($stakeholders as $stakeholder) {
            $stakeholder->notify(new PayrollPreparedNotification($period, $employeeCount));
        }
    }
}
