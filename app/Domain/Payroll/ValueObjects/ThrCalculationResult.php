<?php

declare(strict_types=1);

namespace App\Domain\Payroll\ValueObjects;

use Carbon\Carbon;

final readonly class ThrCalculationResult
{
    public function __construct(
        public float $thrAmount,
        public float $baseSalary,
        public EmployeeTenure $tenure,
        public string $calculationMethod,
        public string $notes,
        public Carbon $calculatedAt
    ) {
    }

    public static function eligible(
        float $thrAmount,
        float $baseSalary,
        EmployeeTenure $tenure,
        string $calculationMethod,
        string $notes
    ): self {
        return new self(
            thrAmount: round($thrAmount, 2),
            baseSalary: $baseSalary,
            tenure: $tenure,
            calculationMethod: $calculationMethod,
            notes: $notes,
            calculatedAt: now()
        );
    }

    public static function notEligible(string $reason, EmployeeTenure $tenure): self
    {
        return new self(
            thrAmount: 0,
            baseSalary: 0,
            tenure: $tenure,
            calculationMethod: 'not_eligible',
            notes: $reason,
            calculatedAt: now()
        );
    }

    public function isEligible(): bool
    {
        return $this->thrAmount > 0;
    }

    public function getFormattedAmount(): string
    {
        return 'Rp ' . number_format($this->thrAmount, 0, ',', '.');
    }

    public function toArray(): array
    {
        return [
            'thr_amount' => $this->thrAmount,
            'base_salary' => $this->baseSalary,
            'months_worked' => $this->tenure->monthsWorked,
            'calculation_method' => $this->calculationMethod,
            'calculation_notes' => $this->notes,
            'calculation_date' => $this->calculatedAt,
            'is_eligible' => $this->isEligible(),
        ];
    }
}