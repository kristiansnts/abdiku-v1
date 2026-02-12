<?php

declare(strict_types=1);

namespace App\Domain\Leave\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'is_paid',
        'deduct_from_balance',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'deduct_from_balance' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveRecords(): HasMany
    {
        return $this->hasMany(LeaveRecord::class);
    }

    /**
     * Get default leave types for Indonesia (v1)
     */
    public static function getDefaults(): array
    {
        return [
            [
                'name' => 'Cuti Tahunan',
                'code' => 'annual',
                'is_paid' => true,
                'deduct_from_balance' => true,
            ],
            [
                'name' => 'Cuti Sakit',
                'code' => 'sick',
                'is_paid' => true,
                'deduct_from_balance' => false,
            ],
            [
                'name' => 'Cuti Tanpa Gaji',
                'code' => 'unpaid',
                'is_paid' => false,
                'deduct_from_balance' => false,
            ],
        ];
    }

    /**
     * Create default leave types for a company
     */
    public static function createDefaultsForCompany(int $companyId): void
    {
        foreach (self::getDefaults() as $leaveType) {
            self::create([
                'company_id' => $companyId,
                ...$leaveType,
            ]);
        }
    }
}
