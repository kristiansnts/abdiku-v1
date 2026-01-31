<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services;

use App\Domain\Attendance\Models\AttendanceCorrectionRequest;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestAttendanceCorrectionService
{
    public function execute(
        AttendanceRaw $attendance,
        array $corrections,
        string $reason,
        User $actor,
    ): AttendanceCorrectionRequest {
        $this->validateRole($actor);

        return DB::transaction(function () use ($attendance, $corrections, $reason, $actor) {
            return AttendanceCorrectionRequest::create([
                'attendance_raw_id' => $attendance->id,
                'original_data' => [
                    'clock_in' => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                ],
                'proposed_data' => $corrections,
                'reason' => $reason,
                'requested_by' => $actor->id,
                'requested_at' => now(),
                'status' => 'PENDING',
            ]);
        });
    }

    private function validateRole(User $actor): void
    {
        if (!$actor->hasRole('hr') && !$actor->hasRole('owner')) {
            throw new UnauthorizedPayrollActionException(
                action: 'request attendance correction',
                requiredRole: 'hr',
            );
        }
    }
}
