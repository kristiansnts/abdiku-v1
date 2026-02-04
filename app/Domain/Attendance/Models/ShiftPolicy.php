<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Models\Company;
use Carbon\Carbon;
use Database\Factories\ShiftPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftPolicy extends Model
{
    use HasFactory;

    protected $table = 'shift_policies';

    protected static function newFactory()
    {
        return ShiftPolicyFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'start_time',
        'end_time',
        'late_after_minutes',
        'minimum_work_hours',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'late_after_minutes' => 'integer',
            'minimum_work_hours' => 'integer',
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
     * Calculate if a given clock-in time is considered late.
     */
    public function isLate(\DateTimeInterface $clockIn): bool
    {
        $clockInCarbon = Carbon::parse($clockIn);
        $shiftStart = Carbon::parse($this->start_time)->setDate(
            (int) $clockInCarbon->format('Y'),
            (int) $clockInCarbon->format('m'),
            (int) $clockInCarbon->format('d')
        );

        $lateThreshold = $shiftStart->copy()->addMinutes($this->late_after_minutes);

        return $clockInCarbon->gt($lateThreshold);
    }

    /**
     * Calculate late minutes for a given clock-in time.
     */
    public function getLateMinutes(\DateTimeInterface $clockIn): int
    {
        if (!$this->isLate($clockIn)) {
            return 0;
        }

        $clockInCarbon = Carbon::parse($clockIn);
        $shiftStart = Carbon::parse($this->start_time)->setDate(
            (int) $clockInCarbon->format('Y'),
            (int) $clockInCarbon->format('m'),
            (int) $clockInCarbon->format('d')
        );

        return (int) $shiftStart->diffInMinutes($clockInCarbon);
    }

    /**
     * Get expected shift duration in hours.
     */
    public function getExpectedHoursAttribute(): float
    {
        return Carbon::parse($this->start_time)->diffInHours(Carbon::parse($this->end_time));
    }
}
