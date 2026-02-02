<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttendanceRequestType: string implements HasLabel
{
    case LATE = 'LATE';
    case CORRECTION = 'CORRECTION';
    case MISSING = 'MISSING';

    public function getLabel(): string
    {
        return match ($this) {
            self::LATE => 'Keterlambatan',
            self::CORRECTION => 'Koreksi Waktu',
            self::MISSING => 'Absen Hilang',
        };
    }
}
