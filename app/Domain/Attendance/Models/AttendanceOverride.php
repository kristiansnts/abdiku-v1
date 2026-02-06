<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceOverride extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'attendance_decision_id',
        'old_classification',
        'new_classification',
        'reason',
        'overridden_by',
        'overridden_at',
    ];

    protected $casts = [
        'old_classification' => AttendanceClassification::class,
        'new_classification' => AttendanceClassification::class,
        'overridden_at' => 'datetime',
    ];

    public function attendanceDecision(): BelongsTo
    {
        return $this->belongsTo(AttendanceDecision::class);
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
