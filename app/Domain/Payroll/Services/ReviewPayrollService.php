<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewPayrollService
{
    public function execute(PayrollPeriod $period, User $actor): void
    {
        $this->validateState($period);
        $this->validateRole($actor);
        $this->validateReadiness($period);

        DB::transaction(function () use ($period) {
            $period->state = PayrollState::REVIEW;
            $period->reviewed_at = now();
            $period->save();
        });
    }

    private function validateState(PayrollPeriod $period): void
    {
        if ($period->state !== PayrollState::DRAFT) {
            throw new InvalidPayrollStateException(
                currentState: $period->state,
                requiredState: PayrollState::DRAFT,
            );
        }
    }

    private function validateRole(User $actor): void
    {
        if (!$actor->hasRole('hr') && !$actor->hasRole('owner')) {
            throw new UnauthorizedPayrollActionException(
                action: 'submit payroll for review',
                requiredRole: 'hr',
            );
        }
    }

    private function validateReadiness(PayrollPeriod $period): void
    {
        $hasDecisions = $period->attendanceDecisions()->exists();

        if (!$hasDecisions) {
            throw new \DomainException(
                'Cannot submit for review: no attendance decisions have been generated.'
            );
        }
    }
}
