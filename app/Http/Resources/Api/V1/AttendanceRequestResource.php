<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_type' => $this->request_type->value,
            'request_type_label' => $this->request_type->getLabel(),
            'requested_clock_in_at' => $this->requested_clock_in_at?->format('Y-m-d H:i:s'),
            'requested_clock_out_at' => $this->requested_clock_out_at?->format('Y-m-d H:i:s'),
            'reason' => $this->reason,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'requested_at' => $this->requested_at->format('Y-m-d H:i:s'),
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'review_note' => $this->review_note,
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
            'attendance' => $this->whenLoaded('attendanceRaw', fn () => new AttendanceRawResource($this->attendanceRaw)),
        ];
    }
}
