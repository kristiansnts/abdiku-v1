<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Policies;

use App\Domain\Payroll\ValueObjects\EmployeeTenure;

final class ThrCalculationPolicy
{
    /**
     * Calculate THR for permanent employees
     * Rule: (Basic Salary + Fixed Allowance) for >= 12 months, prorated for < 12 months
     */
    public function calculatePermanentEmployee(float $monthlyFixedPay, EmployeeTenure $tenure): float
    {
        if ($tenure->hasWorkedFullYear()) {
            return $monthlyFixedPay;
        }

        return $tenure->getProrationFactor() * $monthlyFixedPay;
    }

    /**
     * Calculate THR for contract employees
     * Rule: Prorated based on months worked (minimum 1 month)
     */
    public function calculateContractEmployee(float $monthlyFixedPay, EmployeeTenure $tenure): float
    {
        return $tenure->getProrationFactor() * $monthlyFixedPay;
    }

    /**
     * Calculate THR for daily/freelance employees
     * Rule: Per Permenaker 6/2016
     * - Worked >= 12 months: Average of last 12 months
     * - Worked < 12 months: Average of total months worked
     */
    public function calculateDailyEmployee(
        float $averageMonthlySalary, 
        EmployeeTenure $tenure
    ): float {
        if (!$tenure->hasWorkedAtLeastOneMonth()) {
            return 0;
        }

        if ($tenure->hasWorkedFullYear()) {
            return $averageMonthlySalary;
        }

        return $tenure->getProrationFactor() * $averageMonthlySalary;
    }

    /**
     * Generate calculation explanation based on employee type and tenure
     */
    public function generateCalculationNotes(
        string $employeeType, 
        EmployeeTenure $tenure, 
        float $basePay, 
        float $thrAmount
    ): string {
        $typeLabel = $this->getEmployeeTypeLabel($employeeType);
        $monthsWorkedFormatted = $tenure->getFormattedMonthsWorked();
        
        if ($thrAmount <= 0) {
            return "{$typeLabel} - Tidak berhak THR (masa kerja kurang dari 1 bulan)";
        }

        $salaryLabel = in_array($employeeType, ['daily', 'freelance']) ? 'Rata-rata gaji' : 'Gaji + Tunjangan Tetap';

        if ($tenure->hasWorkedFullYear()) {
            return "{$typeLabel} - THR penuh (masa kerja {$monthsWorkedFormatted}). {$salaryLabel}: Rp " . number_format($basePay, 0, ',', '.');
        }

        $calculation = "THR = ({$monthsWorkedFormatted} / 12) × {$salaryLabel}";
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