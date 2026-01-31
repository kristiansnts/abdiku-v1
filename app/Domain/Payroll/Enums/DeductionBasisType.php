<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeductionBasisType: string implements HasLabel
{
    case BASE_SALARY = 'BASE_SALARY';
    case CAPPED_SALARY = 'CAPPED_SALARY';
    case GROSS_SALARY = 'GROSS_SALARY';

    public function getLabel(): string
    {
        return match ($this) {
            self::BASE_SALARY => 'Base Salary Only',
            self::CAPPED_SALARY => 'Capped Salary',
            self::GROSS_SALARY => 'Gross Salary (Base + Allowances)',
        };
    }
}
