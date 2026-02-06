<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\EvidenceAction;
use App\Domain\Attendance\Enums\EvidenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEvidence extends Model
{
    protected $table = 'attendance_evidences';

    protected $fillable = [
        'attendance_raw_id',
        'type',
        'action',
        'payload',
        'captured_at',
        'hash',
    ];

    protected $casts = [
        'type' => EvidenceType::class,
        'action' => EvidenceAction::class,
        'payload' => 'array',
        'captured_at' => 'datetime',
    ];

    public function attendanceRaw(): BelongsTo
    {
        return $this->belongsTo(AttendanceRaw::class);
    }

    public function getLocationAttribute(): ?array
    {
        if ($this->type === EvidenceType::GEOLOCATION) {
            return [
                'lat' => $this->payload['lat'] ?? null,
                'lng' => $this->payload['lng'] ?? null,
                'accuracy' => $this->payload['accuracy'] ?? null,
            ];
        }

        return null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->type === EvidenceType::PHOTO) {
            return $this->payload['path'] ?? null;
        }

        return null;
    }

    public function getDeviceInfoAttribute(): ?array
    {
        if ($this->type === EvidenceType::DEVICE) {
            return [
                'device_id' => $this->payload['device_id'] ?? null,
                'model' => $this->payload['model'] ?? null,
                'os' => $this->payload['os'] ?? null,
                'app_version' => $this->payload['app_version'] ?? null,
            ];
        }

        return null;
    }
}
