<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

use Filament\Support\Contracts\HasLabel;

enum PayrollAdditionCode: string implements HasLabel
{
    case THR = 'THR';
    case BONUS = 'BONUS';
    case INCENTIVE = 'INCENTIVE';
    case OVERTIME = 'OVERTIME';
    case ADJUSTMENT = 'ADJUSTMENT';

    public function getLabel(): string
    {
        return match ($this) {
            self::THR => 'THR (Tunjangan Hari Raya)',
            self::BONUS => 'Bonus',
            self::INCENTIVE => 'Incentive',
            self::OVERTIME => 'Overtime Pay',
            self::ADJUSTMENT => 'Manual Adjustment',
        };
    }
}
