<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Policies;

use App\Domain\Payroll\ValueObjects\EmployeeTenure;

final class ThrEligibilityPolicy
{
    /**
     * Determines if an employee is eligible for THR based on tenure
     */
    public function isEligible(EmployeeTenure $tenure): bool
    {
        return $tenure->hasWorkedAtLeastOneMonth();
    }

    /**
     * Gets the reason for ineligibility
     */
    public function getIneligibilityReason(EmployeeTenure $tenure): string
    {
        if (!$tenure->hasWorkedAtLeastOneMonth()) {
            return 'Tidak berhak THR (masa kerja kurang dari 1 bulan)';
        }

        return 'Memenuhi syarat THR';
    }

    /**
     * Validates calculation date constraints
     */
    public function isValidCalculationDate(\Carbon\Carbon $calculationDate, \Carbon\Carbon $joinDate): bool
    {
        return $calculationDate->gte($joinDate);
    }
}