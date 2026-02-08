<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Services;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Enums\TimeCorrectionSource;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Domain\Attendance\Models\AttendanceTimeCorrection;
use App\Events\AttendanceRequestReviewed;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveAttendanceRequestService
{
    public function execute(
        AttendanceRequest $request,
        ?string $reviewNote,
        User $actor,
    ): AttendanceRequest {
        $this->validateRequest($request);

        return DB::transaction(function () use ($request, $reviewNote, $actor) {
            // Handle MISSING type - create AttendanceRaw if needed
            if ($request->request_type === AttendanceRequestType::MISSING) {
                $this->handleMissingRequest($request);
            }

            // Create the time correction
            $correction = $this->createTimeCorrection($request, $actor);

            // Update request status and link to correction
            $request->update([
                'status' => AttendanceStatus::APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_note' => $reviewNote,
                'time_correction_id' => $correction->id,
            ]);

            $request = $request->fresh();

            // Dispatch event for notification
            event(new AttendanceRequestReviewed($request, true, $actor));

            return $request;
        });
    }

    private function validateRequest(AttendanceRequest $request): void
    {
        if (! $request->isPending()) {
            throw new \DomainException(
                "Tidak dapat menyetujui pengajuan: status adalah {$request->status->getLabel()}"
            );
        }

        // Check if a correction already exists for this employee+date
        $existingCorrection = AttendanceTimeCorrection::query()
            ->where('employee_id', $request->employee_id)
            ->where('date', $this->getRequestDate($request))
            ->first();

        if ($existingCorrection) {
            throw new \DomainException(
                'Koreksi sudah ada untuk karyawan ini pada tanggal tersebut'
            );
        }
    }

    private function getRequestDate(AttendanceRequest $request): string
    {
        // For MISSING, use the requested_clock_in date
        // For CORRECTION/LATE, use the attendance_raw date
        if ($request->attendance_raw_id) {
            return $request->attendanceRaw->date->format('Y-m-d');
        }

        return $request->requested_clock_in_at->format('Y-m-d');
    }

    private function handleMissingRequest(AttendanceRequest $request): void
    {
        // For MISSING type, create a raw record if it doesn't exist
        if ($request->attendance_raw_id === null) {
            $raw = AttendanceRaw::create([
                'company_id' => $request->company_id,
                'employee_id' => $request->employee_id,
                'date' => $request->requested_clock_in_at->format('Y-m-d'),
                'clock_in' => null,
                'clock_out' => null,
                'source' => AttendanceSource::REQUEST,
                'status' => AttendanceStatus::APPROVED,
            ]);

            // Link the request to this new raw record
            $request->attendance_raw_id = $raw->id;
            $request->save();
        }
    }

    private function createTimeCorrection(
        AttendanceRequest $request,
        User $actor,
    ): AttendanceTimeCorrection {
        return AttendanceTimeCorrection::create([
            'attendance_raw_id' => $request->attendance_raw_id,
            'employee_id' => $request->employee_id,
            'company_id' => $request->company_id,
            'date' => $this->getRequestDate($request),
            'corrected_clock_in' => $request->requested_clock_in_at,
            'corrected_clock_out' => $request->requested_clock_out_at,
            'source_type' => TimeCorrectionSource::EMPLOYEE_REQUEST,
            'source_id' => $request->id,
            'reason' => $request->reason,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);
    }
}
