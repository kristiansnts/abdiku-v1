<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Leave\Models\Holiday;

class MasterHoliday extends Model
{
    protected $fillable = [
        'name',
        'date',
        'external_id',
        'is_cuti_bersama',
    ];

    protected $casts = [
        'date' => 'date',
        'is_cuti_bersama' => 'boolean',
    ];

    public function tenantHolidays(): HasMany
    {
        return $this->hasMany(Holiday::class, 'master_holiday_id');
    }
}
