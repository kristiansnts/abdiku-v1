<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Attendance\Enums\EvidenceType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttendanceEvidenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'payload' => $this->getFormattedPayload(),
            'captured_at' => $this->captured_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getFormattedPayload(): array
    {
        $payload = $this->payload;

        if ($this->type === EvidenceType::PHOTO && isset($payload['path'])) {
            $payload['url'] = Storage::disk('public')->url($payload['path']);
        }

        return $payload;
    }
}
