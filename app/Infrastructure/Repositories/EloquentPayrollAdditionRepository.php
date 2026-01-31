<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Payroll\Contracts\PayrollAdditionRepositoryInterface;
use App\Domain\Payroll\Models\PayrollAddition;
use Illuminate\Support\Collection;

final class EloquentPayrollAdditionRepository implements PayrollAdditionRepositoryInterface
{
    public function create(array $data): PayrollAddition
    {
        return PayrollAddition::create($data);
    }

    public function thrExistsForEmployeeInPeriod(int $employeeId, int $periodId): bool
    {
        return PayrollAddition::where('employee_id', $employeeId)
            ->where('payroll_period_id', $periodId)
            ->where('code', 'THR')
            ->exists();
    }

    public function getExistingThr(int $employeeId, int $periodId): ?PayrollAddition
    {
        return PayrollAddition::where('employee_id', $employeeId)
            ->where('payroll_period_id', $periodId)
            ->where('code', 'THR')
            ->first();
    }

    public function createBatch(array $additions): Collection
    {
        $created = collect();
        
        foreach ($additions as $additionData) {
            $created->push($this->create($additionData));
        }
        
        return $created;
    }

    public function getByPeriodAndCompany(int $periodId, int $companyId): Collection
    {
        return PayrollAddition::where('payroll_period_id', $periodId)
            ->whereHas('employee', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['employee', 'period', 'creator'])
            ->get();
    }
}