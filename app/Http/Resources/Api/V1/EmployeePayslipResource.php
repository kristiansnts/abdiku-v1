<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeePayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period' => [
                'year' => $this->payrollBatch->payrollPeriod->year,
                'month' => $this->payrollBatch->payrollPeriod->month,
                'period_start' => $this->payrollBatch->payrollPeriod->period_start?->format('Y-m-d'),
                'period_end' => $this->payrollBatch->payrollPeriod->period_end?->format('Y-m-d'),
            ],
            'gross_amount' => (float) $this->gross_amount,
            'deduction_amount' => (float) $this->deduction_amount,
            'net_amount' => (float) $this->net_amount,
            'attendance_count' => $this->attendance_count,
            'deductions' => $this->deductions->map(function ($deduction) {
                return [
                    'code' => $deduction->deduction_code,
                    'employee_amount' => (float) $deduction->employee_amount,
                    'employer_amount' => (float) $deduction->employer_amount,
                ];
            }),
            'additions' => $this->additions->map(function ($addition) {
                return [
                    'code' => $addition->addition_code,
                    'description' => $addition->description,
                    'amount' => (float) $addition->amount,
                ];
            }),
            'finalized_at' => $this->payrollBatch->finalized_at?->format('Y-m-d H:i:s'),
        ];
    }
}