<?php

declare(strict_types=1);

namespace App\Domain\Payroll\ValueObjects;

use Carbon\Carbon;

final readonly class EmployeeTenure
{
    public function __construct(
        public Carbon $startDate,
        public Carbon $endDate,
        public float $monthsWorked,
        public int $daysWorked,
        public bool $isResigned
    ) {
    }

    public static function fromDates(Carbon $joinDate, Carbon $endDate, ?Carbon $resignDate = null): self
    {
        $actualEndDate = $resignDate && $resignDate->lt($endDate) ? $resignDate : $endDate;
        
        return new self(
            startDate: $joinDate,
            endDate: $actualEndDate,
            monthsWorked: $joinDate->diffInMonths($actualEndDate),
            daysWorked: (int) $joinDate->diffInDays($actualEndDate),
            isResigned: $resignDate?->lte($endDate) ?? false
        );
    }

    public function hasWorkedAtLeastOneMonth(): bool
    {
        return $this->monthsWorked >= 1;
    }

    public function hasWorkedFullYear(): bool
    {
        return $this->monthsWorked >= 12;
    }

    public function getProrationFactor(): float
    {
        return min($this->monthsWorked / 12, 1.0);
    }

    public function getFormattedMonthsWorked(): string
    {
        return number_format($this->monthsWorked, 1) . ' bulan';
    }
}