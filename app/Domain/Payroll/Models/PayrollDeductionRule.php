<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\DeductionBasisType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeductionRule extends Model
{
    protected $table = 'payroll_deduction_rules';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'basis_type',
        'employee_rate',
        'employer_rate',
        'salary_cap',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'basis_type' => DeductionBasisType::class,
        'employee_rate' => 'decimal:2',
        'employer_rate' => 'decimal:2',
        'salary_cap' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isActive(): bool
    {
        $now = now()->toDateString();

        return $this->effective_from <= $now
            && ($this->effective_to === null || $this->effective_to >= $now);
    }

    public function calculateDeduction(float $salary): array
    {
        $basis = $this->salary_cap && $salary > $this->salary_cap
            ? (float) $this->salary_cap
            : $salary;

        $employeeAmount = $this->employee_rate ? $basis * ($this->employee_rate / 100) : 0;
        $employerAmount = $this->employer_rate ? $basis * ($this->employer_rate / 100) : 0;

        return [
            'basis' => $basis,
            'employee_amount' => round($employeeAmount, 2),
            'employer_amount' => round($employerAmount, 2),
        ];
    }
}
