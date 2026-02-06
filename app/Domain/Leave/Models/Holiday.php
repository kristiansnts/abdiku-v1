<?php

declare(strict_types=1);

namespace App\Domain\Leave\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = [
        'company_id',
        'date',
        'name',
        'is_paid',
    ];

    protected $casts = [
        'date' => 'date',
        'is_paid' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
