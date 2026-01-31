<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Payroll\Enums\DeductionType;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceDecision extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return \Database\Factories\AttendanceDecisionFactory::new();
    }
    
    public $timestamps = false;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'date',
        'classification',
        'payable',
        'deduction_type',
        'deduction_value',
        'rule_version',
        'decided_at',
    ];

    protected $casts = [
        'date' => 'date',
        'classification' => AttendanceClassification::class,
        'payable' => 'boolean',
        'deduction_type' => DeductionType::class,
        'deduction_value' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function override(): HasOne
    {
        return $this->hasOne(AttendanceOverride::class);
    }
}
