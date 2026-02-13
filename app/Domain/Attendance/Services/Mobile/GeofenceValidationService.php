<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\ValueObjects\GeofenceValidationResult;
use App\Models\Company;
use App\Models\CompanyLocation;

class GeofenceValidationService
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function validate(float $lat, float $lng, Company $company, bool $isMocked = false): GeofenceValidationResult
    {
        if ($isMocked) {
            return new GeofenceValidationResult(
                validated: false,
                withinGeofence: false,
                reason: 'Fake GPS/Mock location detected',
            );
        }

        $locations = $company->locations;

        if ($locations->isEmpty()) {
            return new GeofenceValidationResult(
                validated: false,
                withinGeofence: null,
                reason: 'No company locations configured',
            );
        }

        $nearestLocation = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $lat,
                $lng,
                (float) $location->latitude,
                (float) $location->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestLocation = $location;
            }

            if ($distance <= $location->geofence_radius_meters) {
                return new GeofenceValidationResult(
                    validated: true,
                    withinGeofence: true,
                    nearestLocation: $location,
                    distance: $distance,
                );
            }
        }

        return new GeofenceValidationResult(
            validated: true,
            withinGeofence: false,
            nearestLocation: $nearestLocation,
            distance: $minDistance,
            reason: 'Outside all geofence zones',
        );
    }

    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }
}
