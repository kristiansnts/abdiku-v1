<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';

    protected $fillable = [
        'employee_id',
        'base_salary',
        'allowances',
        'effective_from',
        'effective_to',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'allowances' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->effective_to === null;
    }

    public function getTotalAllowancesAttribute(): float
    {
        if (empty($this->allowances)) {
            return 0;
        }

        return (float) array_sum($this->allowances);
    }

    public function getTotalCompensationAttribute(): float
    {
        return (float) $this->base_salary + $this->total_allowances;
    }
}
