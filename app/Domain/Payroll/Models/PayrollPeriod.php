<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Payroll\Enums\PayrollState;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\PayrollPeriodFactory::new();
    }
    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'state',
        'year',
        'month',
        'rule_version',
        'reviewed_at',
        'finalized_by',
        'finalized_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'state' => PayrollState::class,
        'year' => 'integer',
        'month' => 'integer',
        'reviewed_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function attendanceDecisions(): HasMany
    {
        return $this->hasMany(AttendanceDecision::class);
    }

    public function payrollBatch(): HasOne
    {
        return $this->hasOne(PayrollBatch::class);
    }
}
