<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DayOfWeek: int implements HasLabel, HasColor
{
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
    case SUNDAY = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::MONDAY => 'Senin',
            self::TUESDAY => 'Selasa',
            self::WEDNESDAY => 'Rabu',
            self::THURSDAY => 'Kamis',
            self::FRIDAY => 'Jumat',
            self::SATURDAY => 'Sabtu',
            self::SUNDAY => 'Minggu',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MONDAY, self::TUESDAY, self::WEDNESDAY, self::THURSDAY, self::FRIDAY => 'success',
            self::SATURDAY => 'warning',
            self::SUNDAY => 'danger',
        };
    }

    /**
     * Get short label (3 characters).
     */
    public function getShortLabel(): string
    {
        return match ($this) {
            self::MONDAY => 'Sen',
            self::TUESDAY => 'Sel',
            self::WEDNESDAY => 'Rab',
            self::THURSDAY => 'Kam',
            self::FRIDAY => 'Jum',
            self::SATURDAY => 'Sab',
            self::SUNDAY => 'Min',
        };
    }

    /**
     * Get 5-day pattern values (Mon-Fri).
     */
    public static function fiveDayPattern(): array
    {
        return [1, 2, 3, 4, 5];
    }

    /**
     * Get 6-day pattern values (Mon-Sat).
     */
    public static function sixDayPattern(): array
    {
        return [1, 2, 3, 4, 5, 6];
    }

    /**
     * Get all-days pattern values (Mon-Sun).
     */
    public static function allDaysPattern(): array
    {
        return [1, 2, 3, 4, 5, 6, 7];
    }
}
