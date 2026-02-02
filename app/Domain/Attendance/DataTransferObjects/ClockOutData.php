<?php

declare(strict_types=1);

namespace App\Domain\Attendance\DataTransferObjects;

use Carbon\Carbon;

readonly class ClockOutData
{
    public function __construct(
        public Carbon $clockOutAt,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $accuracy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            clockOutAt: Carbon::parse($data['clock_out_at']),
            latitude: isset($data['evidence']['geolocation']['lat'])
                ? (float) $data['evidence']['geolocation']['lat']
                : null,
            longitude: isset($data['evidence']['geolocation']['lng'])
                ? (float) $data['evidence']['geolocation']['lng']
                : null,
            accuracy: isset($data['evidence']['geolocation']['accuracy'])
                ? (float) $data['evidence']['geolocation']['accuracy']
                : null,
        );
    }

    public function hasGeolocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
