<?php

declare(strict_types=1);

namespace App\Domain\Attendance\DataTransferObjects;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

readonly class ClockInData
{
    public function __construct(
        public Carbon $clockInAt,
        public float $latitude,
        public float $longitude,
        public ?float $accuracy,
        public string $deviceId,
        public string $deviceModel,
        public string $deviceOs,
        public string $appVersion,
        public ?UploadedFile $photo = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            clockInAt: Carbon::parse($data['clock_in_at'], 'UTC'),
            latitude: (float) $data['evidence']['geolocation']['lat'],
            longitude: (float) $data['evidence']['geolocation']['lng'],
            accuracy: isset($data['evidence']['geolocation']['accuracy'])
                ? (float) $data['evidence']['geolocation']['accuracy']
                : null,
            deviceId: $data['evidence']['device']['device_id'],
            deviceModel: $data['evidence']['device']['model'],
            deviceOs: $data['evidence']['device']['os'],
            appVersion: $data['evidence']['device']['app_version'],
            photo: $data['evidence']['photo'] ?? null,
        );
    }
}
