<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasLabel;

enum EvidenceAction: string implements HasLabel
{
    case CLOCK_IN = 'CLOCK_IN';
    case CLOCK_OUT = 'CLOCK_OUT';

    public function getLabel(): string
    {
        return match ($this) {
            self::CLOCK_IN => 'Clock In',
            self::CLOCK_OUT => 'Clock Out',
        };
    }
}
