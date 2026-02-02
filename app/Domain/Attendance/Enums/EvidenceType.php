<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasLabel;

enum EvidenceType: string implements HasLabel
{
    case GEOLOCATION = 'GEOLOCATION';
    case DEVICE = 'DEVICE';
    case PHOTO = 'PHOTO';

    public function getLabel(): string
    {
        return match ($this) {
            self::GEOLOCATION => 'Lokasi GPS',
            self::DEVICE => 'Info Perangkat',
            self::PHOTO => 'Foto',
        };
    }
}
