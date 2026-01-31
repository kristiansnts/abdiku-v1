<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Attendance\Models\AttendanceOverride;
use App\Domain\Payroll\Enums\DeductionType;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Models\OverrideRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveOverrideService
{
    public function execute(
        OverrideRequest $request,
        bool $approved,
        ?string $reviewNote,
        User $actor,
    ): OverrideRequest {
        $this->validateState($request);
        $this->validateRole($actor);

        return DB::transaction(function () use ($request, $approved, $reviewNote, $actor) {
            // Update request status
            $request->status = $approved ? 'APPROVED' : 'REJECTED';
            $request->reviewed_by = $actor->id;
            $request->reviewed_at = now();
            $request->review_note = $reviewNote;
            $request->save();

            // If approved, apply the override
            if ($approved) {
                $decision = $request->attendanceDecision;

                $override = AttendanceOverride::create([
                    'attendance_decision_id' => $decision->id,
                    'old_classification' => $request->old_classification,
                    'new_classification' => $request->proposed_classification,
                    'reason' => $request->reason,
                    'overridden_by' => $actor->id,
                    'overridden_at' => now(),
                ]);

                $decision->classification = $request->proposed_classification;
                $decision->payable = $this->isPayable($request->proposed_classification);
                $decision->deduction_type = $this->determineDeductionType($request->proposed_classification);
                $decision->decided_at = now();
                $decision->save();
            }

            return $request;
        });
    }

    private function validateState(OverrideRequest $request): void
    {
        if ($request->status !== 'PENDING') {
            throw new \DomainException(
                "Cannot approve/reject override request: status is {$request->status}"
            );
        }

        $period = $request->attendanceDecision->payrollPeriod;

        if ($period->state !== PayrollState::REVIEW) {
            throw new InvalidPayrollStateException(
                currentState: $period->state,
                requiredState: PayrollState::REVIEW,
            );
        }
    }

    private function validateRole(User $actor): void
    {
        if (!$actor->hasRole('owner')) {
            throw new UnauthorizedPayrollActionException(
                action: 'approve override',
                requiredRole: 'owner',
            );
        }
    }

    private function isPayable(AttendanceClassification $classification): bool
    {
        return in_array($classification, [
            AttendanceClassification::ATTEND,
            AttendanceClassification::LATE,
            AttendanceClassification::PAID_LEAVE,
            AttendanceClassification::PAID_SICK,
            AttendanceClassification::HOLIDAY,
        ], true);
    }

    private function determineDeductionType(AttendanceClassification $classification): DeductionType
    {
        return match ($classification) {
            AttendanceClassification::ABSENT,
            AttendanceClassification::UNPAID_LEAVE,
            AttendanceClassification::UNPAID_SICK => DeductionType::FULL,
            default => DeductionType::NONE,
        };
    }
}
