<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Attendance\Models\ShiftPolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employeeTimezone = $this->employee->timezone ?? 'Asia/Jakarta';
        $shiftPolicy = $this->getShiftPolicyForDate();
        $lateInfo = $this->getLateInfo($shiftPolicy, $employeeTimezone);

        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'clock_in' => $this->clock_in?->setTimezone($employeeTimezone)->format('Y-m-d H:i:s'),
            'clock_out' => $this->clock_out?->setTimezone($employeeTimezone)->format('Y-m-d H:i:s'),
            'source' => $this->source->value,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'is_late' => $lateInfo['is_late'],
            'late_minutes' => $lateInfo['late_minutes'],
            'late_label' => $lateInfo['label'],
            'shift' => $shiftPolicy ? [
                'id' => $shiftPolicy->id,
                'name' => $shiftPolicy->name,
                'start_time' => $shiftPolicy->start_time?->format('H:i'),
                'end_time' => $shiftPolicy->end_time?->format('H:i'),
            ] : null,
            'evidences' => AttendanceEvidenceResource::collection($this->whenLoaded('evidences')),
            'location' => $this->whenLoaded('companyLocation', fn () => new CompanyLocationResource($this->companyLocation)),
            'requests' => AttendanceRequestResource::collection($this->whenLoaded('requests')),
        ];
    }

    /**
     * Get the shift policy applicable for the attendance date.
     */
    private function getShiftPolicyForDate(): ?ShiftPolicy
    {
        $date = $this->date;

        $workAssignment = $this->employee->workAssignments
            ->first(function ($assignment) use ($date) {
                return $assignment->isActiveOn($date);
            });

        return $workAssignment?->shiftPolicy;
    }

    /**
     * Calculate late information for attendance records.
     *
     * @return array{is_late: bool, late_minutes: int, label: string}
     */
    private function getLateInfo(?ShiftPolicy $shiftPolicy, string $employeeTimezone): array
    {
        $default = ['is_late' => false, 'late_minutes' => 0, 'label' => ''];

        if (! $this->clock_in || ! $shiftPolicy) {
            return $default;
        }

        $lateResult = $this->calculateLateMinutes($shiftPolicy, $employeeTimezone);
        if ($lateResult['late_minutes'] <= 0) {
            return $default;
        }

        return [
            'is_late' => true,
            'late_minutes' => $lateResult['late_minutes'],
            'label' => $this->formatLateLabel($lateResult['late_minutes']),
        ];
    }

    /**
     * Calculate late minutes with proper timezone handling.
     */
    private function calculateLateMinutes(ShiftPolicy $shiftPolicy, string $employeeTimezone): array
    {
        $clockInLocal = $this->clock_in->copy()->setTimezone($employeeTimezone);

        $shiftStartTime = $shiftPolicy->start_time->format('H:i:s');
        $shiftStart = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $clockInLocal->format('Y-m-d') . ' ' . $shiftStartTime,
            $employeeTimezone
        );

        $lateThreshold = $shiftStart->copy()->addMinutes($shiftPolicy->late_after_minutes);

        if ($clockInLocal->lte($lateThreshold)) {
            return ['late_minutes' => 0];
        }

        $lateMinutes = (int) $shiftStart->diffInMinutes($clockInLocal);

        return ['late_minutes' => $lateMinutes];
    }

    /**
     * Format the late label based on minutes.
     */
    private function formatLateLabel(int $minutes): string
    {
        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $remainingMinutes = $minutes % 60;

            if ($remainingMinutes > 0) {
                return "Terlambat {$hours} jam {$remainingMinutes} menit";
            }

            return "Terlambat {$hours} jam";
        }

        return "Terlambat {$minutes} menit";
    }
}
