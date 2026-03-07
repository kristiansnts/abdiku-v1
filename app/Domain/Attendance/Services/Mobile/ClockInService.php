<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\DataTransferObjects\ClockInData;
use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Enums\GeofenceStatus;
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
        // Use the date of the clock-in event, not today's server date.
        // This ensures offline clock-ins are stored on the correct calendar day.
        $attendanceDate = $data->clockInAt->copy()
            ->timezone(config('app.timezone'))
            ->toDateString();

        $existingAttendance = AttendanceRaw::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $attendanceDate)
            ->first();

        if ($existingAttendance && $existingAttendance->clock_in !== null) {
            throw AttendanceException::alreadyClockedIn();
        }

        $company        = $employee->company;
        $geofenceResult = $this->geofenceService->validate(
            $data->latitude,
            $data->longitude,
            $company,
            $data->isMocked,
        );

        // Map geofence status → attendance status.
        // Clock-in is ALWAYS recorded — never blocked by location.
        $status = match ($geofenceResult->geofenceStatus) {
            GeofenceStatus::VALID          => AttendanceStatus::APPROVED,
            GeofenceStatus::OUTSIDE_RADIUS,
            GeofenceStatus::INVALID_LOCATION,
            GeofenceStatus::MOCK_LOCATION  => AttendanceStatus::PENDING,
        };

        return DB::transaction(function () use ($employee, $data, $geofenceResult, $status, $existingAttendance, $attendanceDate) {
            if ($existingAttendance) {
                $existingAttendance->update([
                    'company_location_id' => $geofenceResult->nearestLocation?->id,
                    'clock_in'            => $data->clockInAt,
                    'source'              => AttendanceSource::MOBILE,
                    'status'              => $status,
                ]);
                $attendance = $existingAttendance;
            } else {
                $attendance = AttendanceRaw::create([
                    'company_id'          => $employee->company_id,
                    'company_location_id' => $geofenceResult->nearestLocation?->id,
                    'employee_id'         => $employee->id,
                    'date'                => $attendanceDate,
                    'clock_in'            => $data->clockInAt,
                    'clock_out'           => null,
                    'source'              => AttendanceSource::MOBILE,
                    'status'              => $status,
                ]);
            }

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

            return $attendance->load('evidences', 'companyLocation', 'employee');
        });
    }
}
