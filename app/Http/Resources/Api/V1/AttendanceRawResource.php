<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRawResource extends JsonResource
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
            'evidences' => AttendanceEvidenceResource::collection($this->whenLoaded('evidences')),
            'location' => $this->whenLoaded('companyLocation', fn () => new CompanyLocationResource($this->companyLocation)),
        ];
    }
}
