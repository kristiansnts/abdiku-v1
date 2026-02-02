<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\Enums\EvidenceType;
use App\Domain\Attendance\Models\AttendanceEvidence;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\ValueObjects\GeofenceValidationResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EvidenceStorageService
{
    public function storeGeolocation(
        AttendanceRaw $attendance,
        float $lat,
        float $lng,
        ?float $accuracy,
        GeofenceValidationResult $validation,
    ): AttendanceEvidence {
        $payload = [
            'lat' => $lat,
            'lng' => $lng,
            'accuracy' => $accuracy,
            'validated' => $validation->validated,
            'within_geofence' => $validation->withinGeofence,
            'nearest_location_id' => $validation->nearestLocation?->id,
            'distance_meters' => $validation->distance,
        ];

        return AttendanceEvidence::create([
            'attendance_raw_id' => $attendance->id,
            'type' => EvidenceType::GEOLOCATION,
            'payload' => $payload,
            'captured_at' => now(),
            'hash' => $this->generateHash($payload),
        ]);
    }

    public function storeDevice(
        AttendanceRaw $attendance,
        string $deviceId,
        string $model,
        string $os,
        string $appVersion,
    ): AttendanceEvidence {
        $payload = [
            'device_id' => $deviceId,
            'model' => $model,
            'os' => $os,
            'app_version' => $appVersion,
        ];

        return AttendanceEvidence::create([
            'attendance_raw_id' => $attendance->id,
            'type' => EvidenceType::DEVICE,
            'payload' => $payload,
            'captured_at' => now(),
            'hash' => $this->generateHash($payload),
        ]);
    }

    public function storePhoto(
        AttendanceRaw $attendance,
        UploadedFile $photo,
    ): AttendanceEvidence {
        $path = $photo->store(
            'attendance/photos/'.now()->format('Y/m'),
            'public'
        );

        $payload = [
            'path' => $path,
            'size' => $photo->getSize(),
            'mime_type' => $photo->getMimeType(),
        ];

        return AttendanceEvidence::create([
            'attendance_raw_id' => $attendance->id,
            'type' => EvidenceType::PHOTO,
            'payload' => $payload,
            'captured_at' => now(),
            'hash' => $this->generateHash($payload),
        ]);
    }

    private function generateHash(array $payload): string
    {
        return hash('sha256', json_encode($payload).config('app.key'));
    }
}
