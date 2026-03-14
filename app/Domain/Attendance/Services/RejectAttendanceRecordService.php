<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Events\AttendanceRecordReviewed;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class RejectAttendanceRecordService
{
    public function execute(AttendanceRaw $record, string $reviewNote, User $actor): void
    {
        if ($record->isLocked()) {
            throw new \RuntimeException('Rekap kehadiran yang terkunci tidak dapat diubah.');
        }

        $record->update([
            'status'      => AttendanceStatus::REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $reviewNote,
        ]);

        Log::info('Attendance record rejected', [
            'attendance_id' => $record->id,
            'employee_id'   => $record->employee_id,
            'reviewed_by'   => $actor->id,
        ]);

        event(new AttendanceRecordReviewed($record, false, $actor));
    }
}
