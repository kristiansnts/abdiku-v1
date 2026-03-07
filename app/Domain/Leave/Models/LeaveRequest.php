<?php

declare(strict_types=1);

namespace App\Domain\Leave\Models;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Models\Holiday;
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
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => LeaveRequestStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
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

        $companyId = $this->employee?->company_id
            ?? Employee::query()->where('id', $this->employee_id)->value('company_id');

        $holidayDates = [];
        if ($companyId) {
            $holidayDates = Holiday::query()
                ->where('company_id', $companyId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->map(fn (Carbon $date) => $date->format('Y-m-d'))
                ->all();
        }

        $holidayMap = array_flip($holidayDates);

        return $start->diffInDaysFiltered(function (Carbon $date) use ($holidayMap) {
            // Exclude weekends and holidays
            return !$date->isWeekend() && !isset($holidayMap[$date->format('Y-m-d')]);
        }, $end) + 1; // +1 to include end date
    }
}
