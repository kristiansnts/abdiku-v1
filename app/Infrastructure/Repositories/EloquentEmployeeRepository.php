<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Payroll\Contracts\EmployeeRepositoryInterface;
use App\Models\Employee;
use Illuminate\Support\Collection;

final class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function findWithCompensation(int $employeeId): ?Employee
    {
        return Employee::with(['compensations' => function ($query) {
            $query->whereNull('effective_to')
                  ->latest('effective_from');
        }])->find($employeeId);
    }

    public function getActiveEmployeesByCompany(int $companyId): Collection
    {
        return Employee::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->with(['compensations' => function ($query) {
                $query->whereNull('effective_to')
                      ->latest('effective_from');
            }])
            ->get();
    }

    public function findWithActiveCompensation(int $employeeId): ?Employee
    {
        $employee = $this->findWithCompensation($employeeId);
        
        if (!$employee || !$employee->compensations->isNotEmpty()) {
            return null;
        }

        return $employee;
    }

    public function isActiveEmployee(int $employeeId, int $companyId): bool
    {
        return Employee::where('id', $employeeId)
            ->where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->exists();
    }
}