<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Attendance\Models\AttendanceRaw;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employeeTimezone = $this->getEmployeeTimezone();

        return [
            'id' => $this->id,
            'type' => $this->getActivityType(),
            'datetime' => $this->getActivityDatetime()->setTimezone($employeeTimezone)->format('Y-m-d\TH:i:s'),
            'status' => $this->getActivityStatus(),
            'label' => $this->getActivityLabel(),
        ];
    }

    private function getEmployeeTimezone(): string
    {
        if ($this->resource instanceof AttendanceRaw) {
            return $this->resource->employee->timezone ?? 'Asia/Jakarta';
        }

        return $this->resource->employee->timezone ?? 'Asia/Jakarta';
    }

    private function getActivityType(): string
    {
        if ($this->resource instanceof AttendanceRaw) {
            return $this->resource->clock_out ? 'CLOCK_OUT' : 'CLOCK_IN';
        }

        return strtoupper(str_replace('-', '_', $this->resource->request_type->value));
    }

    private function getActivityDatetime(): \Carbon\Carbon
    {
        if ($this->resource instanceof AttendanceRaw) {
            return $this->resource->clock_out ?? $this->resource->clock_in ?? $this->resource->date;
        }

        return $this->resource->requested_at;
    }

    private function getActivityStatus(): string
    {
        return strtoupper($this->resource->status->value);
    }

    private function getActivityLabel(): string
    {
        if ($this->resource instanceof AttendanceRaw) {
            return $this->resource->status->getLabel();
        }

        return $this->resource->request_type->getLabel();
    }
}
