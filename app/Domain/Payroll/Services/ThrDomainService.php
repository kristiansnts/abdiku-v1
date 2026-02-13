<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Policies\ThrCalculationPolicy;
use App\Domain\Payroll\Policies\ThrEligibilityPolicy;
use App\Domain\Payroll\ValueObjects\EmployeeTenure;
use App\Domain\Payroll\ValueObjects\ThrCalculationResult;
use Carbon\Carbon;

final class ThrDomainService
{
    public function __construct(
        private readonly ThrCalculationPolicy $calculationPolicy,
        private readonly ThrEligibilityPolicy $eligibilityPolicy
    ) {
    }

    /**
     * Calculate THR for an employee with given parameters
     * This is pure business logic with no external dependencies
     */
    public function calculateThr(
        Carbon $joinDate,
        ?Carbon $resignDate,
        Carbon $calculationDate,
        float $baseSalary,
        string $employeeType,
        ?int $workingDaysInYear = null
    ): ThrCalculationResult {
        // Validate inputs
        if (!$this->eligibilityPolicy->isValidCalculationDate($calculationDate, $joinDate)) {
            throw new \InvalidArgumentException('Calculation date cannot be before join date');
        }

        if (!$this->calculationPolicy->isValidEmployeeType($employeeType)) {
            throw new \InvalidArgumentException("Invalid employee type: {$employeeType}");
        }

        // Calculate tenure
        $tenure = EmployeeTenure::fromDates($joinDate, $calculationDate, $resignDate);

        // Check eligibility
        if (!$this->eligibilityPolicy->isEligible($tenure, $employeeType, $calculationDate)) {
            return ThrCalculationResult::notEligible(
                $this->eligibilityPolicy->getIneligibilityReason($tenure, $employeeType, $calculationDate),
                $tenure
            );
        }

        // Calculate THR amount based on employee type
        $thrAmount = match ($employeeType) {
            'permanent' => $this->calculationPolicy->calculatePermanentEmployee($baseSalary, $tenure),
            'contract' => $this->calculationPolicy->calculateContractEmployee($baseSalary, $tenure),
            'daily', 'freelance' => $this->calculateDailyEmployeeThr($baseSalary, $tenure, $workingDaysInYear ?? 260),
            default => throw new \InvalidArgumentException("Unsupported employee type: {$employeeType}")
        };

        // Generate calculation method and notes
        $calculationMethod = $this->calculationPolicy->getCalculationMethod($employeeType, $tenure);
        $notes = $this->calculationPolicy->generateCalculationNotes($employeeType, $tenure, $baseSalary, $thrAmount);

        return ThrCalculationResult::eligible(
            $thrAmount,
            $baseSalary,
            $tenure,
            $calculationMethod,
            $notes
        );
    }

    /**
     * Special handling for daily/freelance employees using working days
     */
    private function calculateDailyEmployeeThr(
        float $monthlySalary, 
        EmployeeTenure $tenure, 
        int $workingDaysInYear
    ): float {
        return $this->calculationPolicy->calculateDailyEmployee(
            $monthlySalary,
            $tenure->daysWorked,
            $workingDaysInYear
        );
    }
}