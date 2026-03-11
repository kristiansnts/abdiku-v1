<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoLead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'sandbox_company_id',
        'ip_address',
    ];

    public function sandboxCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sandbox_company_id');
    }
}
