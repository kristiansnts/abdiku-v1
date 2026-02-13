<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Policies;

use App\Domain\Payroll\ValueObjects\EmployeeTenure;

final class ThrEligibilityPolicy
{
    /**
     * Determines if an employee is eligible for THR based on tenure and employment laws
     */
    public function isEligible(EmployeeTenure $tenure, string $employeeType, \Carbon\Carbon $holidayDate): bool
    {
        // 1. Minimum tenure check (1 month)
        if (!$tenure->hasWorkedAtLeastOneMonth()) {
            return false;
        }

        // 2. Resignation Rule check
        if ($tenure->isResigned) {
            $daysBeforeHoliday = $tenure->endDate->diffInDays($holidayDate, false);

            if ($employeeType === 'permanent') {
                // Permanent (PKWTT) lose eligibility if resigned > 30 days before holiday
                return $daysBeforeHoliday <= 30;
            }

            // Contract (PKWT) lose eligibility if contract ends BEFORE holiday
            return $daysBeforeHoliday <= 0;
        }

        return true;
    }

    /**
     * Gets the reason for ineligibility
     */
    public function getIneligibilityReason(EmployeeTenure $tenure, string $employeeType, \Carbon\Carbon $holidayDate): string
    {
        if (!$tenure->hasWorkedAtLeastOneMonth()) {
            return 'Tidak berhak THR (masa kerja kurang dari 1 bulan)';
        }

        if ($tenure->isResigned) {
            $daysBeforeHoliday = $tenure->endDate->diffInDays($holidayDate, false);

            if ($employeeType === 'permanent' && $daysBeforeHoliday > 30) {
                return 'Karyawan tetap mengundurkan diri lebih dari 30 hari sebelum hari raya';
            }

            if ($employeeType !== 'permanent' && $daysBeforeHoliday > 0) {
                return 'Karyawan kontrak/harian berakhir sebelum hari raya';
            }
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