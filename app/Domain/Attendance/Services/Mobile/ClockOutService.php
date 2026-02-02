<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\DataTransferObjects\ClockOutData;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Exceptions\Api\AttendanceException;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class ClockOutService
{
    public function __construct(
        private readonly GeofenceValidationService $geofenceService,
        private readonly EvidenceStorageService $evidenceService,
    ) {}

    public function execute(Employee $employee, ClockOutData $data): AttendanceRaw
    {
        $today = now()->toDateString();

        $attendance = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (! $attendance) {
            throw AttendanceException::notClockedIn();
        }

        if ($attendance->clock_out !== null) {
            throw AttendanceException::alreadyClockedOut();
        }

        if ($attendance->isLocked()) {
            throw AttendanceException::attendanceLocked();
        }

        return DB::transaction(function () use ($attendance, $employee, $data) {
            $attendance->update([
                'clock_out' => $data->clockOutAt,
            ]);

            if ($data->hasGeolocation()) {
                $company = $employee->company;
                $geofenceResult = $this->geofenceService->validate(
                    $data->latitude,
                    $data->longitude,
                    $company
                );

                $this->evidenceService->storeGeolocation(
                    $attendance,
                    $data->latitude,
                    $data->longitude,
                    $data->accuracy,
                    $geofenceResult,
                );
            }

            return $attendance->load('evidences', 'companyLocation');
        });
    }
}
