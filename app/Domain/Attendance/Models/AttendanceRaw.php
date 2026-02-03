<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Employee;
use Database\Factories\AttendanceRawFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRaw extends Model
{
    use HasFactory;
    
    protected $table = 'attendance_raw';

    protected static function newFactory()
    {
        return AttendanceRawFactory::new();
    }

    protected $fillable = [
        'company_id',
        'company_location_id',
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'source',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'source' => AttendanceSource::class,
        'status' => AttendanceStatus::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyLocation(): BelongsTo
    {
        return $this->belongsTo(CompanyLocation::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(AttendanceEvidence::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
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

    public function isLocked(): bool
    {
        return $this->status === AttendanceStatus::LOCKED;
    }

    public function canBeModified(): bool
    {
        return ! $this->isLocked();
    }
}
