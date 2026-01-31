<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeductionType: string implements HasLabel, HasColor
{
    case NONE = 'NONE';
    case FULL = 'FULL';
    case PERCENTAGE = 'PERCENTAGE';

    public function getLabel(): string
    {
        return match ($this) {
            self::NONE => 'Tidak Ada',
            self::FULL => 'Penuh',
            self::PERCENTAGE => 'Persentase',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NONE => 'success',
            self::FULL => 'warning',
            self::PERCENTAGE => 'info',
        };
    }
}
