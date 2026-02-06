<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\ShiftPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employeeTimezone = $this->getEmployeeTimezone();
        $lateInfo = $this->getLateInfo();

        $data = [
            'id' => $this->id,
            'type' => $this->getActivityType(),
            'datetime' => $this->getActivityDatetime()->setTimezone($employeeTimezone)->format('Y-m-d\TH:i:s'),
            'status' => $this->getActivityStatus(),
            'label' => $this->getActivityLabel(),
        ];

        if ($lateInfo['is_late']) {
            $data['is_late'] = true;
            $data['late_minutes'] = $lateInfo['late_minutes'];
            $data['late_label'] = $lateInfo['label'];
        }

        return $data;
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

    /**
     * Calculate late information for attendance records.
     * Checks if clock_in was late based on employee's shift policy.
     *
     * @return array{is_late: bool, late_minutes: int, label: string}
     */
    private function getLateInfo(): array
    {
        $default = ['is_late' => false, 'late_minutes' => 0, 'label' => ''];

        if (! $this->resource instanceof AttendanceRaw) {
            return $default;
        }

        if (! $this->resource->clock_in) {
            return $default;
        }

        $shiftPolicy = $this->getShiftPolicyForDate();
        if (! $shiftPolicy) {
            return $default;
        }

        $lateResult = $this->calculateLateMinutes($shiftPolicy);
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
     * Both clock_in and shift start time are compared in the same timezone.
     */
    private function calculateLateMinutes(ShiftPolicy $shiftPolicy): array
    {
        $employeeTimezone = $this->resource->employee->timezone ?? 'Asia/Jakarta';

        // Convert clock_in to employee's local time
        $clockInLocal = $this->resource->clock_in->copy()->setTimezone($employeeTimezone);

        // Get shift start time in local timezone (treat start_time as local time)
        $shiftStartTime = $shiftPolicy->start_time->format('H:i:s');
        $shiftStart = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $clockInLocal->format('Y-m-d') . ' ' . $shiftStartTime,
            $employeeTimezone
        );

        // Calculate late threshold
        $lateThreshold = $shiftStart->copy()->addMinutes($shiftPolicy->late_after_minutes);

        // Check if late
        if ($clockInLocal->lte($lateThreshold)) {
            return ['late_minutes' => 0];
        }

        // Calculate minutes late from shift start (not from threshold)
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

    /**
     * Get the shift policy applicable for the attendance date.
     * Uses eager-loaded workAssignments to avoid N+1 queries.
     */
    private function getShiftPolicyForDate(): ?ShiftPolicy
    {
        $date = $this->resource->date;

        // Use the eager-loaded workAssignments collection
        $workAssignment = $this->resource->employee->workAssignments
            ->first(function ($assignment) use ($date) {
                return $assignment->isActiveOn($date);
            });

        return $workAssignment?->shiftPolicy;
    }
}
