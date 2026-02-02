<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendanceSource: string implements HasLabel, HasColor
{
    case MACHINE = 'MACHINE';
    case REQUEST = 'REQUEST';
    case IMPORT = 'IMPORT';
    case MOBILE = 'MOBILE';

    public function getLabel(): string
    {
        return match ($this) {
            self::MACHINE => 'Mesin',
            self::REQUEST => 'Permintaan',
            self::IMPORT => 'Impor',
            self::MOBILE => 'Aplikasi Mobile',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MACHINE => 'success',
            self::REQUEST => 'warning',
            self::IMPORT => 'info',
            self::MOBILE => 'primary',
        };
    }
}
