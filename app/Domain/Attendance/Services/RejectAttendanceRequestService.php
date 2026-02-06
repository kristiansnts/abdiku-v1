<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Models\User;

class RejectAttendanceRequestService
{
    public function execute(
        AttendanceRequest $request,
        string $reviewNote,
        User $actor,
    ): AttendanceRequest {
        if (! $request->isPending()) {
            throw new \DomainException(
                "Tidak dapat menolak pengajuan: status adalah {$request->status->getLabel()}"
            );
        }

        $request->update([
            'status' => AttendanceStatus::REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $reviewNote,
        ]);

        return $request->fresh();
    }
}
