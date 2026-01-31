<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Contracts;

use App\Domain\Payroll\Models\PayrollPeriod;
use Illuminate\Support\Collection;

interface PayrollPeriodRepositoryInterface
{
    /**
     * Find payroll period by ID
     */
    public function find(int $periodId): ?PayrollPeriod;

    /**
     * Get all periods for a company
     */
    public function getByCompany(int $companyId): Collection;

    /**
     * Get periods formatted for UI selection
     */
    public function getFormattedOptionsForCompany(int $companyId): array;

    /**
     * Check if period belongs to company
     */
    public function belongsToCompany(int $periodId, int $companyId): bool;
}