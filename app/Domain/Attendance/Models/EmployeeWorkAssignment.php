<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Models\Employee;
use Carbon\Carbon;
use Database\Factories\EmployeeWorkAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkAssignment extends Model
{
    use HasFactory;

    protected $table = 'employee_work_assignments';

    protected static function newFactory()
    {
        return EmployeeWorkAssignmentFactory::new();
    }

    protected $fillable = [
        'employee_id',
        'shift_policy_id',
        'work_pattern_id',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftPolicy(): BelongsTo
    {
        return $this->belongsTo(ShiftPolicy::class);
    }

    public function workPattern(): BelongsTo
    {
        return $this->belongsTo(WorkPattern::class);
    }

    /**
     * Check if this assignment is currently active (no end date).
     */
    public function isActive(): bool
    {
        return $this->effective_to === null;
    }

    /**
     * Check if this assignment is active on a specific date.
     */
    public function isActiveOn(\DateTimeInterface $date): bool
    {
        $checkDate = Carbon::parse($date)->startOfDay();

        if ($checkDate->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to === null) {
            return true;
        }

        return $checkDate->lte($this->effective_to);
    }

    /**
     * Scope to find assignments active on a specific date.
     */
    public function scopeActiveOn(Builder $query, \DateTimeInterface $date): Builder
    {
        $dateString = Carbon::parse($date)->toDateString();

        return $query
            ->where('effective_from', '<=', $dateString)
            ->where(function (Builder $q) use ($dateString) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $dateString);
            });
    }

    /**
     * Scope to find currently active assignments.
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->whereNull('effective_to');
    }

    /**
     * Scope to find assignments for a specific employee.
     */
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
