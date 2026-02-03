<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollBatch extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory()
    {
        return \Database\Factories\PayrollBatchFactory::new();
    }

    protected $fillable = [
        'company_id',
        'payroll_period_id',
        'total_amount',
        'employee_count',
        'rule_version',
        'finalized_by',
        'finalized_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'finalized_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PayrollRow::class);
    }
}
