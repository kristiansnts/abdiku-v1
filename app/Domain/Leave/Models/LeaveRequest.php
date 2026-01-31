<?php

declare(strict_types=1);

namespace App\Domain\Leave\Models;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Enums\LeaveType;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'leave_type' => LeaveType::class,
        'status' => LeaveRequestStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function leaveRecords(): HasMany
    {
        return $this->hasMany(LeaveRecord::class);
    }

    public function isPending(): bool
    {
        return $this->status === LeaveRequestStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === LeaveRequestStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === LeaveRequestStatus::REJECTED;
    }

    public function getDateRangeAttribute(): array
    {
        $period = CarbonPeriod::create($this->start_date, $this->end_date);
        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request) {
            if (!$request->total_days) {
                $request->total_days = $request->calculateBusinessDays();
            }
        });
    }

    public function calculateBusinessDays(): int
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        return $start->diffInDaysFiltered(function (Carbon $date) {
            // Exclude weekends (Saturday=6, Sunday=0)
            return !$date->isWeekend();
        }, $end) + 1; // +1 to include end date
    }
}
