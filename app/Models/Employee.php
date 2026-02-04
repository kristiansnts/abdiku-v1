<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Attendance\Models\EmployeeWorkAssignment;
use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Models\PayrollRow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'join_date',
        'resign_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'resign_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function compensations(): HasMany
    {
        return $this->hasMany(EmployeeCompensation::class);
    }

    public function payrollRows(): HasMany
    {
        return $this->hasMany(PayrollRow::class);
    }

    public function workAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkAssignment::class);
    }

    public function activeWorkAssignment(): HasOne
    {
        return $this->hasOne(EmployeeWorkAssignment::class)
            ->whereNull('effective_to')
            ->latest('effective_from');
    }

    /**
     * Get the work assignment active on a specific date.
     */
    public function getWorkAssignmentOn(\DateTimeInterface $date): ?EmployeeWorkAssignment
    {
        return $this->workAssignments()
            ->activeOn($date)
            ->with(['shiftPolicy', 'workPattern'])
            ->first();
    }
}
