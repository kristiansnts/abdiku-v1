<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'device_model',
        'device_os',
        'app_version',
        'is_active',
        'is_blocked',
        'block_reason',
        'blocked_by',
        'blocked_at',
        'last_login_at',
        'last_ip_address',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function isBlocked(): bool
    {
        return $this->is_blocked;
    }

    public function isActive(): bool
    {
        return $this->is_active && ! $this->is_blocked;
    }

    public function block(User $blockedBy, string $reason): void
    {
        $this->update([
            'is_blocked' => true,
            'is_active' => false,
            'block_reason' => $reason,
            'blocked_by' => $blockedBy->id,
            'blocked_at' => now(),
        ]);

        // Revoke all tokens for this user when device is blocked
        $this->user->tokens()->delete();
    }

    public function unblock(): void
    {
        $this->update([
            'is_blocked' => false,
            'block_reason' => null,
            'blocked_by' => null,
            'blocked_at' => null,
        ]);
    }

    public function activate(): void
    {
        // Deactivate all other devices for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }

    public function recordLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_ip_address' => $ipAddress,
        ]);
    }
}
