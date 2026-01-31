<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Payroll\Contracts\PayrollPeriodRepositoryInterface;
use App\Domain\Payroll\Models\PayrollPeriod;
use Illuminate\Support\Collection;

final class EloquentPayrollPeriodRepository implements PayrollPeriodRepositoryInterface
{
    public function find(int $periodId): ?PayrollPeriod
    {
        return PayrollPeriod::find($periodId);
    }

    public function getByCompany(int $companyId): Collection
    {
        return PayrollPeriod::where('company_id', $companyId)
            ->orderBy('period_start', 'desc')
            ->get();
    }

    public function getFormattedOptionsForCompany(int $companyId): array
    {
        return $this->getByCompany($companyId)
            ->mapWithKeys(function (PayrollPeriod $period) {
                $label = $period->period_start->format('M Y') . ' - ' . $period->period_end->format('M Y');
                return [$period->id => $label];
            })
            ->toArray();
    }

    public function belongsToCompany(int $periodId, int $companyId): bool
    {
        return PayrollPeriod::where('id', $periodId)
            ->where('company_id', $companyId)
            ->exists();
    }
}