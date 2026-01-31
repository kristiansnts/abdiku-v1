<?php

declare(strict_types=1);

namespace App\Domain\Leave\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeaveType: string implements HasLabel, HasColor
{
    case PAID = 'PAID';
    case UNPAID = 'UNPAID';
    case SICK_PAID = 'SICK_PAID';
    case SICK_UNPAID = 'SICK_UNPAID';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAID => 'Cuti Dibayar',
            self::UNPAID => 'Cuti Tidak Dibayar',
            self::SICK_PAID => 'Sakit Dibayar',
            self::SICK_UNPAID => 'Sakit Tidak Dibayar',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PAID => 'info',
            self::UNPAID => 'gray',
            self::SICK_PAID => 'info',
            self::SICK_UNPAID => 'gray',
        };
    }
}
