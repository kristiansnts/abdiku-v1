<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Attendance\Models\AttendanceDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverrideRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attendance_decision_id',
        'old_classification',
        'proposed_classification',
        'reason',
        'requested_by',
        'requested_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'old_classification' => AttendanceClassification::class,
        'proposed_classification' => AttendanceClassification::class,
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function attendanceDecision(): BelongsTo
    {
        return $this->belongsTo(AttendanceDecision::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
