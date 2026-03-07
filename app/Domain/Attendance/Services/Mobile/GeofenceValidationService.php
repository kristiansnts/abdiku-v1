<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services\Mobile;

use App\Domain\Attendance\Enums\GeofenceStatus;
use App\Domain\Attendance\ValueObjects\GeofenceValidationResult;
use App\Models\Company;

class GeofenceValidationService
{
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Distance (metres) beyond the geofence boundary that is still considered
     * "slightly outside" and will be flagged for review rather than invalid.
     */
    private const SOFT_BOUNDARY_METERS = 200;

    /**
     * Distance (metres) from the nearest location centre beyond which the
     * clock-in is considered an invalid location rather than a borderline one.
     */
    private const INVALID_DISTANCE_METERS = 5000;

    public function validate(float $lat, float $lng, Company $company, bool $isMocked = false): GeofenceValidationResult
    {
        if ($isMocked) {
            return new GeofenceValidationResult(
                validated: true,
                withinGeofence: false,
                geofenceStatus: GeofenceStatus::MOCK_LOCATION,
                reason: 'Fake GPS / mock location detected',
            );
        }

        $locations = $company->locations;

        if ($locations->isEmpty()) {
            // No locations configured — record as valid so employees are not blocked.
            return new GeofenceValidationResult(
                validated: true,
                withinGeofence: null,
                geofenceStatus: GeofenceStatus::VALID,
                reason: 'No company locations configured',
            );
        }

        $nearestLocation = null;
        $minDistance     = PHP_FLOAT_MAX;

        foreach ($locations as $location) {
            $distance = $this->calculateDistance(
                $lat,
                $lng,
                (float) $location->latitude,
                (float) $location->longitude,
            );

            if ($distance < $minDistance) {
                $minDistance     = $distance;
                $nearestLocation = $location;
            }

            if ($distance <= $location->geofence_radius_meters) {
                return new GeofenceValidationResult(
                    validated: true,
                    withinGeofence: true,
                    geofenceStatus: GeofenceStatus::VALID,
                    nearestLocation: $location,
                    distance: $distance,
                );
            }
        }

        // Employee is outside every geofence — determine how far outside.
        $overshoot = $minDistance - $nearestLocation->geofence_radius_meters;

        if ($minDistance >= self::INVALID_DISTANCE_METERS) {
            $status = GeofenceStatus::INVALID_LOCATION;
            $reason = sprintf(
                'Employee is %.0f m from the nearest office (%s) — flagged as invalid location.',
                $minDistance,
                $nearestLocation->name,
            );
        } elseif ($overshoot < self::SOFT_BOUNDARY_METERS) {
            $status = GeofenceStatus::OUTSIDE_RADIUS;
            $reason = sprintf(
                'Employee is %.0f m outside the geofence boundary of %s — requires review.',
                $overshoot,
                $nearestLocation->name,
            );
        } else {
            $status = GeofenceStatus::OUTSIDE_RADIUS;
            $reason = sprintf(
                'Employee is %.0f m from %s (%.0f m outside boundary) — requires review.',
                $minDistance,
                $nearestLocation->name,
                $overshoot,
            );
        }

        return new GeofenceValidationResult(
            validated: true,
            withinGeofence: false,
            geofenceStatus: $status,
            nearestLocation: $nearestLocation,
            distance: $minDistance,
            reason: $reason,
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
