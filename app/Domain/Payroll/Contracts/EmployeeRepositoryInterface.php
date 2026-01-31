<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Contracts;

use App\Models\Employee;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    /**
     * Find employee by ID with compensation relationship
     */
    public function findWithCompensation(int $employeeId): ?Employee;

    /**
     * Get all active employees for a company
     */
    public function getActiveEmployeesByCompany(int $companyId): Collection;

    /**
     * Get employee with active compensation
     */
    public function findWithActiveCompensation(int $employeeId): ?Employee;

    /**
     * Check if employee exists and is active
     */
    public function isActiveEmployee(int $employeeId, int $companyId): bool;
}