<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GeofenceStatus: string implements HasColor, HasLabel
{
    /** Employee is within the configured geofence radius. */
    case VALID = 'VALID';

    /** Employee is outside the radius but within 200 m of the boundary. Requires review. */
    case OUTSIDE_RADIUS = 'OUTSIDE_RADIUS';

    /** Employee is far from any office location (≥ 5 km). Flagged for review. */
    case INVALID_LOCATION = 'INVALID_LOCATION';

    /** A mock / fake GPS application was detected. */
    case MOCK_LOCATION = 'MOCK_LOCATION';

    public function getLabel(): string
    {
        return match ($this) {
            self::VALID            => 'Lokasi Valid',
            self::OUTSIDE_RADIUS   => 'Di Luar Radius (Perlu Review)',
            self::INVALID_LOCATION => 'Lokasi Tidak Valid',
            self::MOCK_LOCATION    => 'Lokasi Palsu (Mock GPS)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VALID            => 'success',
            self::OUTSIDE_RADIUS   => 'warning',
            self::INVALID_LOCATION => 'danger',
            self::MOCK_LOCATION    => 'danger',
        };
    }

    public function requiresReview(): bool
    {
        return match ($this) {
            self::VALID  => false,
            default      => true,
        };
    }
}
