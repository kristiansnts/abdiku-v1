<?php

namespace App\Listeners;

use App\Events\PayrollFinalized;
use App\Helpers\NotificationRecipientHelper;
use App\Notifications\PayrollFinalizedEmployeeNotification;
use App\Notifications\PayrollFinalizedStakeholderNotification;

class NotifyAllOfPayrollFinalized
{
    public function handle(PayrollFinalized $event): void
    {
        $period = $event->payrollPeriod;
        $batch = $event->payrollBatch;

        // Notify stakeholders (HR + owners)
        $stakeholders = NotificationRecipientHelper::getStakeholders($period->company_id);
        foreach ($stakeholders as $stakeholder) {
            $stakeholder->notify(new PayrollFinalizedStakeholderNotification($period, $batch));
        }

        // Notify all employees
        $employees = NotificationRecipientHelper::getAllEmployeeUsers($period->company_id);
        foreach ($employees as $employee) {
            $employee->notify(new PayrollFinalizedEmployeeNotification($period));
        }
    }
}
