<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendanceClassification: string implements HasLabel, HasColor
{
    case ATTEND = 'ATTEND';
    case LATE = 'LATE';
    case ABSENT = 'ABSENT';
    case PAID_LEAVE = 'PAID_LEAVE';
    case UNPAID_LEAVE = 'UNPAID_LEAVE';
    case HOLIDAY = 'HOLIDAY';
    case PAID_SICK = 'PAID_SICK';
    case UNPAID_SICK = 'UNPAID_SICK';

    public function getLabel(): string
    {
        return match ($this) {
            self::ATTEND => 'Hadir',
            self::LATE => 'Terlambat',
            self::ABSENT => 'Absen',
            self::PAID_LEAVE => 'Cuti Dibayar',
            self::UNPAID_LEAVE => 'Cuti Tidak Dibayar',
            self::HOLIDAY => 'Libur',
            self::PAID_SICK => 'Sakit Dibayar',
            self::UNPAID_SICK => 'Sakit Tidak Dibayar',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ATTEND => 'success',
            self::LATE => 'warning',
            self::ABSENT => 'danger',
            self::PAID_LEAVE => 'info',
            self::UNPAID_LEAVE => 'gray',
            self::HOLIDAY => 'primary',
            self::PAID_SICK => 'info',
            self::UNPAID_SICK => 'gray',
        };
    }
}
