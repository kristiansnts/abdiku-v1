<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employeeTimezone = $this->employee->timezone ?? 'Asia/Jakarta';

        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'clock_in' => $this->clock_in?->setTimezone($employeeTimezone)->format('H:i'),
            'clock_out' => $this->clock_out?->setTimezone($employeeTimezone)->format('H:i'),
            'status' => $this->status->value,
        ];
    }
}
