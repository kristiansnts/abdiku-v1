<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\PayrollAdditionCode;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAddition extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return \Database\Factories\PayrollAdditionFactory::new();
    }
    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'code',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'code' => PayrollAdditionCode::class,
        'amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
