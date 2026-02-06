<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\TimeCorrectionSource;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceTimeCorrection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attendance_raw_id',
        'employee_id',
        'company_id',
        'date',
        'corrected_clock_in',
        'corrected_clock_out',
        'source_type',
        'source_id',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'corrected_clock_in' => 'datetime',
        'corrected_clock_out' => 'datetime',
        'source_type' => TimeCorrectionSource::class,
        'approved_at' => 'datetime',
    ];

    public function attendanceRaw(): BelongsTo
    {
        return $this->belongsTo(AttendanceRaw::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the source request if this correction came from an employee request.
     */
    public function sourceRequest(): ?AttendanceRequest
    {
        if ($this->source_type !== TimeCorrectionSource::EMPLOYEE_REQUEST) {
            return null;
        }

        return AttendanceRequest::find($this->source_id);
    }
}
