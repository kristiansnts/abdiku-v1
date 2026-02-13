<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRow extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = false;

    protected static function newFactory()
    {
        return \Database\Factories\PayrollRowFactory::new();
    }

    protected $fillable = [
        'payroll_batch_id',
        'employee_id',
        'gross_amount',
        'deduction_amount',
        'tax_amount',
        'net_amount',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function payrollBatch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollRowDeduction::class);
    }

    public function additions(): HasMany
    {
        return $this->hasMany(PayrollRowAddition::class);
    }

    public function getTotalEmployeeDeductionsAttribute(): float
    {
        return (float) $this->deductions->sum('employee_amount');
    }

    public function getTotalEmployerDeductionsAttribute(): float
    {
        return (float) $this->deductions->sum('employer_amount');
    }

    public function getTotalAdditionsAttribute(): float
    {
        return (float) $this->additions->sum('amount');
    }

    public function getAttendanceCountAttribute(): int
    {
        if (!$this->payrollBatch?->payrollPeriod) {
            return 0;
        }

        $period = $this->payrollBatch->payrollPeriod;

        return $period->attendanceDecisions()
            ->where('employee_id', $this->employee_id)
            ->where('payable', true)
            ->count();
    }
}
