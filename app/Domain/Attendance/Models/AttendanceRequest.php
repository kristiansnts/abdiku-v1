<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Database\Factories\AttendanceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRequest extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return AttendanceRequestFactory::new();
    }
    
    protected $fillable = [
        'employee_id',
        'company_id',
        'attendance_raw_id',
        'request_type',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'reason',
        'status',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'request_type' => AttendanceRequestType::class,
        'status' => AttendanceStatus::class,
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function attendanceRaw(): BelongsTo
    {
        return $this->belongsTo(AttendanceRaw::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === AttendanceStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === AttendanceStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === AttendanceStatus::REJECTED;
    }
}
