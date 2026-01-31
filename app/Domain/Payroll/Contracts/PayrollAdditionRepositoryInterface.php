<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Contracts;

use App\Domain\Payroll\Models\PayrollAddition;
use Illuminate\Support\Collection;

interface PayrollAdditionRepositoryInterface
{
    /**
     * Create a new payroll addition
     */
    public function create(array $data): PayrollAddition;

    /**
     * Check if THR already exists for employee in period
     */
    public function thrExistsForEmployeeInPeriod(int $employeeId, int $periodId): bool;

    /**
     * Get existing THR for employee in period
     */
    public function getExistingThr(int $employeeId, int $periodId): ?PayrollAddition;

    /**
     * Create multiple THR additions in batch
     */
    public function createBatch(array $additions): Collection;

    /**
     * Get all additions for a period and company
     */
    public function getByPeriodAndCompany(int $periodId, int $companyId): Collection;
}