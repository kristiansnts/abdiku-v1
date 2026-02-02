<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Attendance\ValueObjects\AttendanceStatusResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusResource extends JsonResource
{
    public function __construct(
        private readonly AttendanceStatusResult $status,
    ) {
        parent::__construct($status);
    }

    public function toArray(Request $request): array
    {
        return $this->status->toArray();
    }
}
