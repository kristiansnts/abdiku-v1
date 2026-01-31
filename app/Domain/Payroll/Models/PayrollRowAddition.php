<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRowAddition extends Model
{
    protected $fillable = [
        'payroll_row_id',
        'addition_code',
        'amount',
        'source_reference',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payrollRow(): BelongsTo
    {
        return $this->belongsTo(PayrollRow::class);
    }

    public function sourceAddition(): BelongsTo
    {
        return $this->belongsTo(PayrollAddition::class, 'source_reference');
    }
}
