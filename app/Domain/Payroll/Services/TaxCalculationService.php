<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Models\Employee;

final class TaxCalculationService
{
    /**
     * Calculate monthly PPh21 using TER (Tarif Efektif Rata-rata) 2024
     */
    public function calculateMonthlyTax(Employee $employee, float $grossAmount): array
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
        
        $rate = $this->getTerRate($category, $grossAmount);
        
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

    /**
     * Placeholder for TER 2024 Rate Tables
     * In a full implementation, this should pull from a database table or config
     */
    private function getTerRate(string $category, float $gross): float
    {
        // Sample simplified rates for Category A (2024 Rules)
        if ($category === 'A') {
            if ($gross <= 5400000) return 0;
            if ($gross <= 5650000) return 0.25;
            if ($gross <= 5950000) return 0.5;
            if ($gross <= 6300000) return 0.75;
            if ($gross <= 6750000) return 1.0;
            if ($gross <= 7500000) return 1.25;
            if ($gross <= 8550000) return 1.5;
            if ($gross <= 9650000) return 1.75;
            if ($gross <= 10650000) return 2.0;
            // ... more rates follow ...
            return 5.0; // Default for higher brackets in this PoC
        }

        // Default to a safe minimum if category B or C is hit
        return 2.0;
    }
}
