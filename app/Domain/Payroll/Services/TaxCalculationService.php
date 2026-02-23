<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Models\Employee;

final class TaxCalculationService
{
    /**
     * Calculate monthly PPh21 using TER (Tarif Efektif Rata-rata) 2024
     */
    public function calculateMonthlyTax(
        Employee $employee,
        float $grossAmount,
        ?\DateTimeInterface $asOf = null
    ): array
    {
        if ($grossAmount <= 0) {
            return [
                'tax_amount' => 0.0,
                'rate' => 0.0,
                'category' => 'A'
            ];
        }

        $ptkpStatus = $employee->ptkp_status ?? 'TK/0';
        $category = $this->getTerCategory($ptkpStatus);
        
        $rate = $this->getTerRate($category, $grossAmount, $asOf);
        
        return [
            'tax_amount' => (float) round($grossAmount * ($rate / 100)),
            'rate' => (float) ($rate / 100),
            'category' => $category
        ];
    }

    /**
     * Determine TER Category based on PTKP Status
     */
    private function getTerCategory(string $ptkp): string
    {
        return match ($ptkp) {
            'TK/0', 'TK/1', 'K/0' => 'A',
            'TK/2', 'TK/3', 'K/1', 'K/2' => 'B',
            'K/3' => 'C',
            default => 'A'
        };
    }

    private function getTerRate(string $category, float $gross, ?\DateTimeInterface $asOf = null): float
    {
        $tables = $this->getTerTableForDate($asOf ?? now());
        $table = $tables[$category] ?? $tables['A'];

        foreach ($table as [$upper, $rate]) {
            if ($gross <= $upper) {
                return $rate;
            }
        }

        return 34.0;
    }

    /**
     * Resolve TER tables by effective date.
     */
    private function getTerTableForDate(\DateTimeInterface $asOf): array
    {
        $tablesByDate = config('payroll.ter_tables', []);
        if (!$tablesByDate) {
            throw new \RuntimeException('TER tables are not configured.');
        }

        $effectiveDates = array_keys($tablesByDate);
        sort($effectiveDates);

        $selectedDate = null;
        foreach ($effectiveDates as $date) {
            if ($asOf->format('Y-m-d') >= $date) {
                $selectedDate = $date;
            }
        }

        $selectedDate = $selectedDate ?? $effectiveDates[0];

        return $tablesByDate[$selectedDate];
    }
}
