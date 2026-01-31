<?php

declare(strict_types=1);

namespace App\Application\Payroll\Services;

use App\Application\Payroll\DTOs\ThrCalculationRequest;
use App\Domain\Payroll\Contracts\EmployeeRepositoryInterface;
use App\Domain\Payroll\Contracts\PayrollAdditionRepositoryInterface;
use App\Domain\Payroll\Contracts\PayrollPeriodRepositoryInterface;
use App\Domain\Payroll\Services\ThrDomainService;
use App\Domain\Payroll\ValueObjects\ThrCalculationResult;

final class ThrCalculationApplicationService
{
    public function __construct(
        private readonly ThrDomainService $thrDomainService,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly PayrollPeriodRepositoryInterface $periodRepository,
        private readonly PayrollAdditionRepositoryInterface $additionRepository
    ) {
    }

    /**
     * Calculate THR for a single employee
     */
    public function calculateForEmployee(ThrCalculationRequest $request): ThrCalculationResult
    {
        // Validate that employee exists and has compensation
        $employee = $this->employeeRepository->findWithActiveCompensation($request->employeeId);
        if (!$employee) {
            throw new \InvalidArgumentException('Employee not found or has no active compensation');
        }

        // Validate that period exists and belongs to company
        if (!$this->periodRepository->belongsToCompany($request->periodId, $request->companyId)) {
            throw new \InvalidArgumentException('Payroll period not found or does not belong to company');
        }

        $period = $this->periodRepository->find($request->periodId);
        if (!$period) {
            throw new \InvalidArgumentException('Payroll period not found');
        }

        // Get active compensation
        $compensation = $employee->compensations->first();
        if (!$compensation) {
            throw new \InvalidArgumentException('Employee has no active compensation');
        }

        // Calculate THR using domain service
        return $this->thrDomainService->calculateThr(
            joinDate: $employee->join_date,
            resignDate: $employee->resign_date,
            calculationDate: $period->period_end,
            baseSalary: (float) $compensation->base_salary,
            employeeType: $request->employeeType,
            workingDaysInYear: $request->workingDaysInYear
        );
    }

    /**
     * Calculate and save THR for an employee
     */
    public function calculateAndCreateThr(ThrCalculationRequest $request, int $createdBy): array
    {
        // Check if THR already exists
        if ($this->additionRepository->thrExistsForEmployeeInPeriod($request->employeeId, $request->periodId)) {
            throw new \InvalidArgumentException('THR already exists for this employee in this period');
        }

        // Calculate THR
        $result = $this->calculateForEmployee($request);

        // Only create if eligible
        if (!$result->isEligible()) {
            throw new \InvalidArgumentException('Employee is not eligible for THR: ' . $result->notes);
        }

        // Create the payroll addition
        $addition = $this->additionRepository->create([
            'employee_id' => $request->employeeId,
            'payroll_period_id' => $request->periodId,
            'code' => 'THR',
            'amount' => $result->thrAmount,
            'description' => $result->notes,
            'created_by' => $createdBy,
        ]);

        return [
            'addition' => $addition,
            'calculation_result' => $result,
        ];
    }

    /**
     * Get THR calculation preview without saving
     */
    public function getCalculationPreview(ThrCalculationRequest $request): array
    {
        try {
            $result = $this->calculateForEmployee($request);
            
            return [
                'success' => true,
                'result' => $result,
                'existing_thr' => $this->additionRepository->getExistingThr($request->employeeId, $request->periodId),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}