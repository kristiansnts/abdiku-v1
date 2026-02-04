<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\DayOfWeek;
use App\Models\Company;
use Carbon\Carbon;
use Database\Factories\WorkPatternFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkPattern extends Model
{
    use HasFactory;

    protected $table = 'work_patterns';

    protected static function newFactory()
    {
        return WorkPatternFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'working_days',
    ];

    protected function casts(): array
    {
        return [
            'working_days' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkAssignment::class);
    }

    /**
     * Check if a given date is a working day in this pattern.
     */
    public function isWorkingDay(\DateTimeInterface $date): bool
    {
        // PHP's N format gives ISO-8601 day number (1=Monday, 7=Sunday)
        $dayOfWeek = (int) $date->format('N');

        return in_array($dayOfWeek, $this->working_days, true);
    }

    /**
     * Get the number of working days per week.
     */
    public function getWorkingDaysCountAttribute(): int
    {
        return count($this->working_days);
    }

    /**
     * Get working days as DayOfWeek enum instances.
     */
    public function getWorkingDaysAsEnums(): array
    {
        return array_map(
            fn (int $day) => DayOfWeek::from($day),
            $this->working_days
        );
    }

    /**
     * Count working days in a date range.
     */
    public function countWorkingDaysInRange(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $count = 0;
        $current = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        while ($current <= $endDate) {
            if ($this->isWorkingDay($current)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
