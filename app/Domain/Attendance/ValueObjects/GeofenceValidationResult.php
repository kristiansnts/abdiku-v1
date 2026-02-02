<?php

declare(strict_types=1);

namespace App\Domain\Attendance\ValueObjects;

use App\Models\CompanyLocation;

readonly class GeofenceValidationResult
{
    public function __construct(
        public bool $validated,
        public ?bool $withinGeofence,
        public ?CompanyLocation $nearestLocation = null,
        public ?float $distance = null,
        public ?string $reason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'validated' => $this->validated,
            'within_geofence' => $this->withinGeofence,
            'nearest_location_id' => $this->nearestLocation?->id,
            'nearest_location_name' => $this->nearestLocation?->name,
            'distance_meters' => $this->distance !== null ? round($this->distance, 2) : null,
            'reason' => $this->reason,
        ];
    }
}
