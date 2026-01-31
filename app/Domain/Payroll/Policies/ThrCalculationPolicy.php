<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Policies;

use App\Domain\Payroll\ValueObjects\EmployeeTenure;

final class ThrCalculationPolicy
{
    /**
     * Calculate THR for permanent employees
     * Rule: Full salary for >= 12 months, prorated for < 12 months
     */
    public function calculatePermanentEmployee(float $baseSalary, EmployeeTenure $tenure): float
    {
        if ($tenure->hasWorkedFullYear()) {
            return $baseSalary; // Full THR
        }

        return $tenure->getProrationFactor() * $baseSalary;
    }

    /**
     * Calculate THR for contract employees
     * Rule: Always prorated based on months worked (minimum 1 month)
     */
    public function calculateContractEmployee(float $baseSalary, EmployeeTenure $tenure): float
    {
        return $tenure->getProrationFactor() * $baseSalary;
    }

    /**
     * Calculate THR for daily/freelance employees
     * Rule: Based on actual working days vs total days in year
     */
    public function calculateDailyEmployee(
        float $monthlySalary, 
        int $actualWorkDays, 
        int $totalWorkingDaysInYear = 365
    ): float {
        if ($actualWorkDays <= 0 || $totalWorkingDaysInYear <= 0) {
            return 0;
        }

        return ($actualWorkDays / $totalWorkingDaysInYear) * $monthlySalary;
    }

    /**
     * Generate calculation explanation based on employee type and tenure
     */
    public function generateCalculationNotes(
        string $employeeType, 
        EmployeeTenure $tenure, 
        float $baseSalary, 
        float $thrAmount
    ): string {
        $typeLabel = $this->getEmployeeTypeLabel($employeeType);
        $monthsWorkedFormatted = $tenure->getFormattedMonthsWorked();
        
        if ($thrAmount <= 0) {
            return "{$typeLabel} - Tidak berhak THR (masa kerja kurang dari 1 bulan)";
        }

        if ($tenure->hasWorkedFullYear() && $employeeType === 'permanent') {
            return "{$typeLabel} - THR penuh (masa kerja {$monthsWorkedFormatted})";
        }

        $calculation = match ($employeeType) {
            'daily', 'freelance' => "THR = (Hari kerja / 365) × Gaji bulanan",
            default => "THR = ({$monthsWorkedFormatted} / 12) × Rp " . number_format($baseSalary, 0, ',', '.')
        };

        $resignedStatus = $tenure->isResigned ? ' (Karyawan yang mengundurkan diri)' : '';
        
        return "{$typeLabel}{$resignedStatus} - {$calculation} = Rp " . number_format($thrAmount, 0, ',', '.');
    }

    /**
     * Get human-readable employee type label
     */
    private function getEmployeeTypeLabel(string $employeeType): string
    {
        return match ($employeeType) {
            'permanent' => 'Karyawan Tetap',
            'contract' => 'Karyawan Kontrak', 
            'daily' => 'Karyawan Harian',
            'freelance' => 'Freelance',
            default => 'Karyawan'
        };
    }

    /**
     * Validate employee type
     */
    public function isValidEmployeeType(string $employeeType): bool
    {
        return in_array($employeeType, ['permanent', 'contract', 'daily', 'freelance']);
    }

    /**
     * Get method identifier for calculation tracking
     */
    public function getCalculationMethod(string $employeeType, EmployeeTenure $tenure): string
    {
        if (!$tenure->hasWorkedAtLeastOneMonth()) {
            return 'ineligible';
        }

        return match ($employeeType) {
            'permanent' => $tenure->hasWorkedFullYear() ? 'permanent_full' : 'permanent_prorated',
            'contract' => 'contract_prorated',
            'daily', 'freelance' => 'daily_prorated',
            default => 'unknown'
        };
    }
}