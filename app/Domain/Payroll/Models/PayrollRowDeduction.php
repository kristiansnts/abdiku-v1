<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRowDeduction extends Model
{
    protected $fillable = [
        'payroll_row_id',
        'deduction_code',
        'employee_amount',
        'employer_amount',
        'rule_snapshot',
    ];

    protected $casts = [
        'employee_amount' => 'decimal:2',
        'employer_amount' => 'decimal:2',
        'rule_snapshot' => 'array',
    ];

    public function payrollRow(): BelongsTo
    {
        return $this->belongsTo(PayrollRow::class);
    }
}
