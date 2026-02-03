<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSalaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'base_salary' => (float) $this->base_salary,
            'allowances' => $this->allowances ?? [],
            'total_allowances' => $this->total_allowances,
            'total_compensation' => $this->total_compensation,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
        ];
    }
}
