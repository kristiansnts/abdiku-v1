<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employeeTimezone = $this->employee->timezone ?? 'Asia/Jakarta';

        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'clock_in' => $this->clock_in?->setTimezone($employeeTimezone)->format('Y-m-d H:i:s'),
            'clock_out' => $this->clock_out?->setTimezone($employeeTimezone)->format('Y-m-d H:i:s'),
            'source' => $this->source->value,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'shift' => $this->whenLoaded('shiftPolicy', fn () => [
                'id' => $this->shiftPolicy->id,
                'name' => $this->shiftPolicy->name,
                'start_time' => $this->shiftPolicy->start_time?->format('H:i'),
                'end_time' => $this->shiftPolicy->end_time?->format('H:i'),
            ]),
            'evidences' => AttendanceEvidenceResource::collection($this->whenLoaded('evidences')),
            'location' => $this->whenLoaded('companyLocation', fn () => new CompanyLocationResource($this->companyLocation)),
            'requests' => AttendanceRequestResource::collection($this->whenLoaded('requests')),
        ];
    }
}
