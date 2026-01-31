<?php

declare(strict_types=1);

namespace App\Application\Payroll\Services;

use App\Application\Payroll\DTOs\ThrCalculationRequest;
use App\Domain\Payroll\Contracts\EmployeeRepositoryInterface;
use App\Domain\Payroll\Contracts\PayrollAdditionRepositoryInterface;
use App\Domain\Payroll\Contracts\PayrollPeriodRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class BulkThrCalculationApplicationService
{
    public function __construct(
        private readonly ThrCalculationApplicationService $thrCalculationService,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly PayrollPeriodRepositoryInterface $periodRepository,
        private readonly PayrollAdditionRepositoryInterface $additionRepository
    ) {
    }

    /**
     * Generate preview for bulk THR calculation
     */
    public function generatePreview(
        int $companyId,
        int $periodId,
        string $defaultEmployeeType = 'permanent',
        int $workingDaysInYear = 260
    ): array {
        // Validate period
        if (!$this->periodRepository->belongsToCompany($periodId, $companyId)) {
            throw new \InvalidArgumentException('Payroll period not found or does not belong to company');
        }

        $period = $this->periodRepository->find($periodId);
        if (!$period) {
            throw new \InvalidArgumentException('Payroll period not found');
        }

        // Get all active employees
        $employees = $this->employeeRepository->getActiveEmployeesByCompany($companyId);

        if ($employees->isEmpty()) {
            return [
                'employees' => [],
                'summary' => [
                    'total_employees' => 0,
                    'eligible_employees' => 0,
                    'total_thr_amount' => 0,
                ],
                'errors' => [],
            ];
        }

        $previewData = [];
        $totalThrAmount = 0;
        $eligibleCount = 0;
        $errors = [];

        foreach ($employees as $employee) {
            try {
                // Skip if THR already exists
                if ($this->additionRepository->thrExistsForEmployeeInPeriod($employee->id, $periodId)) {
                    continue;
                }

                $request = new ThrCalculationRequest(
                    employeeId: $employee->id,
                    periodId: $periodId,
                    companyId: $companyId,
                    employeeType: $defaultEmployeeType,
                    workingDaysInYear: $workingDaysInYear
                );

                $preview = $this->thrCalculationService->getCalculationPreview($request);

                if ($preview['success']) {
                    $result = $preview['result'];
                    if ($result->isEligible()) {
                        $previewData[] = [
                            'employee_id' => $employee->id,
                            'employee_name' => $employee->name,
                            'thr_amount' => $result->thrAmount,
                            'months_worked' => $result->tenure->monthsWorked,
                            'calculation_notes' => $result->notes,
                            'eligible' => true,
                        ];
                        $totalThrAmount += $result->thrAmount;
                        $eligibleCount++;
                    }
                } else {
                    $errors[] = $employee->name . ': ' . $preview['error'];
                }
            } catch (\Exception $e) {
                $errors[] = $employee->name . ': ' . $e->getMessage();
            }
        }

        return [
            'employees' => $previewData,
            'summary' => [
                'total_employees' => $employees->count(),
                'eligible_employees' => $eligibleCount,
                'total_thr_amount' => $totalThrAmount,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Execute bulk THR calculation
     */
    public function executeBulkCalculation(
        int $companyId,
        int $periodId,
        int $createdBy,
        string $defaultEmployeeType = 'permanent',
        int $workingDaysInYear = 260
    ): array {
        return DB::transaction(function () use ($companyId, $periodId, $createdBy, $defaultEmployeeType, $workingDaysInYear) {
            // Get all active employees
            $employees = $this->employeeRepository->getActiveEmployeesByCompany($companyId);
            
            $successCount = 0;
            $skippedCount = 0;
            $errors = [];
            $createdAdditions = [];

            foreach ($employees as $employee) {
                try {
                    // Skip if THR already exists
                    if ($this->additionRepository->thrExistsForEmployeeInPeriod($employee->id, $periodId)) {
                        $skippedCount++;
                        continue;
                    }

                    $request = new ThrCalculationRequest(
                        employeeId: $employee->id,
                        periodId: $periodId,
                        companyId: $companyId,
                        employeeType: $defaultEmployeeType,
                        workingDaysInYear: $workingDaysInYear
                    );

                    $result = $this->thrCalculationService->calculateAndCreateThr($request, $createdBy);
                    $createdAdditions[] = $result['addition'];
                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = $employee->name . ': ' . $e->getMessage();
                }
            }

            return [
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'error_count' => count($errors),
                'errors' => $errors,
                'created_additions' => $createdAdditions,
            ];
        });
    }
}