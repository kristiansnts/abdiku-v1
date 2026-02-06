<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $period = $this->payrollBatch->payrollPeriod;
        $monthName = \Carbon\Carbon::create($period->year, $period->month)->translatedFormat('M Y');

        return [
            'id' => $this->id,
            'period' => $monthName,
            'net_amount' => (float) $this->net_amount,
        ];
    }
}
