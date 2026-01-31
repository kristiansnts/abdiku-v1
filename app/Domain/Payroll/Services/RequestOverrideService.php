<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Models\OverrideRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestOverrideService
{
    public function execute(
        AttendanceDecision $decision,
        AttendanceClassification $proposedClassification,
        string $reason,
        User $actor,
    ): OverrideRequest {
        $this->validateState($decision);
        $this->validateRole($actor);

        return DB::transaction(function () use ($decision, $proposedClassification, $reason, $actor) {
            return OverrideRequest::create([
                'attendance_decision_id' => $decision->id,
                'old_classification' => $decision->classification,
                'proposed_classification' => $proposedClassification,
                'reason' => $reason,
                'requested_by' => $actor->id,
                'requested_at' => now(),
                'status' => 'PENDING',
            ]);
        });
    }

    private function validateState(AttendanceDecision $decision): void
    {
        $period = $decision->payrollPeriod;

        if ($period->state !== PayrollState::DRAFT && $period->state !== PayrollState::REVIEW) {
            throw new InvalidPayrollStateException(
                currentState: $period->state,
                requiredState: PayrollState::REVIEW,
            );
        }
    }

    private function validateRole(User $actor): void
    {
        if (!$actor->hasRole('hr') && !$actor->hasRole('owner')) {
            throw new UnauthorizedPayrollActionException(
                action: 'request override',
                requiredRole: 'hr',
            );
        }
    }
}
