<?php

namespace App\Domain\Payroll\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PayrollState: string implements HasLabel, HasColor
{
    case DRAFT = 'DRAFT';
    case REVIEW = 'REVIEW';
    case FINALIZED = 'FINALIZED';
    case LOCKED = 'LOCKED';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REVIEW => 'Review',
            self::FINALIZED => 'Finalized',
            self::LOCKED => 'Locked',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::REVIEW => 'warning',
            self::FINALIZED => 'success',
            self::LOCKED => 'danger',
        };
    }
}