<?php

namespace App\Listeners;

use App\Events\AttendanceOverrideRequiresOwner;
use App\Helpers\NotificationRecipientHelper;
use App\Notifications\OverrideRequestNotification;

class NotifyOwnerOfOverrideRequest
{
    public function handle(AttendanceOverrideRequiresOwner $event): void
    {
        $overrideRequest = $event->overrideRequest;
        $requestedBy = $event->requestedBy;
        $companyId = $overrideRequest->attendanceDecision->employee->company_id;

        $ownerUsers = NotificationRecipientHelper::getOwnerUsers($companyId);

        foreach ($ownerUsers as $owner) {
            $owner->notify(new OverrideRequestNotification($overrideRequest, $requestedBy));
        }
    }
}
