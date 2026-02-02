<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\DataTransferObjects\ClockInData;
use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Exceptions\Api\AttendanceException;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class ClockInService
{
    public function __construct(
        private readonly GeofenceValidationService $geofenceService,
        private readonly EvidenceStorageService $evidenceService,
    ) {}

    public function execute(Employee $employee, ClockInData $data): AttendanceRaw
    {
        $today = now()->toDateString();

        $existingAttendance = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->first();

        if ($existingAttendance) {
            throw AttendanceException::alreadyClockedIn();
        }

        $company = $employee->company;
        $geofenceResult = $this->geofenceService->validate(
            $data->latitude,
            $data->longitude,
            $company
        );

        $status = $geofenceResult->withinGeofence === true
            ? AttendanceStatus::APPROVED
            : AttendanceStatus::PENDING;

        return DB::transaction(function () use ($employee, $data, $geofenceResult, $status) {
            $attendance = AttendanceRaw::create([
                'company_id' => $employee->company_id,
                'company_location_id' => $geofenceResult->nearestLocation?->id,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'clock_in' => $data->clockInAt,
                'clock_out' => null,
                'source' => AttendanceSource::MOBILE,
                'status' => $status,
            ]);

            $this->evidenceService->storeGeolocation(
                $attendance,
                $data->latitude,
                $data->longitude,
                $data->accuracy,
                $geofenceResult,
            );

            $this->evidenceService->storeDevice(
                $attendance,
                $data->deviceId,
                $data->deviceModel,
                $data->deviceOs,
                $data->appVersion,
            );

            if ($data->photo) {
                $this->evidenceService->storePhoto($attendance, $data->photo);
            }

            return $attendance->load('evidences', 'companyLocation');
        });
    }
}
