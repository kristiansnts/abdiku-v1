<?php

declare(strict_types=1);

namespace App\Domain\Leave\Services;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestReviewedNotification;

class RejectLeaveRequestService
{
    public function execute(LeaveRequest $request, User $rejector, string $reason): void
    {
        // Validate request is pending
        if (!$request->isPending()) {
            throw new \RuntimeException('Only pending leave requests can be rejected');
        }

        // Validate rejector has permission (HR or OWNER)
        if (!$rejector->hasRole(['hr', 'owner'])) {
            throw new \RuntimeException('User does not have permission to reject leave requests');
        }

        $request->update([
            'status' => LeaveRequestStatus::REJECTED,
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $request->employee->user?->notify(new LeaveRequestReviewedNotification($request, false, $rejector));
    }
}
