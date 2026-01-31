<?php

declare(strict_types=1);

namespace App\Domain\Leave\Services;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Models\LeaveRequest;
use App\Models\User;

class RejectLeaveRequestService
{
    public function execute(LeaveRequest $request, User $rejector, string $reason): void
    {
        // Validate request is pending
        if (!$request->isPending()) {
            throw new \RuntimeException('Only pending leave requests can be rejected');
        }

        // Validate rejector has permission (HR or OWNER)
        if (!in_array($rejector->role, ['HR', 'OWNER'])) {
            throw new \RuntimeException('User does not have permission to reject leave requests');
        }

        $request->update([
            'status' => LeaveRequestStatus::REJECTED,
            'approved_by' => $rejector->id, // Store who rejected
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // TODO: Send notification to employee
        // Notification::send($request->employee, new LeaveRequestRejectedNotification($request));
    }
}
