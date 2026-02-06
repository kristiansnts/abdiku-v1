<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasLabel;

enum TimeCorrectionSource: string implements HasLabel
{
    case EMPLOYEE_REQUEST = 'EMPLOYEE_REQUEST';
    case HR_CORRECTION = 'HR_CORRECTION';

    public function getLabel(): string
    {
        return match ($this) {
            self::EMPLOYEE_REQUEST => 'Pengajuan Karyawan',
            self::HR_CORRECTION => 'Koreksi HR',
        };
    }
}
