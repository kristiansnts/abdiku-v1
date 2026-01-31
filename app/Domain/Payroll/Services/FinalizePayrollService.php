<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Exceptions\InvalidPayrollStateException;
use App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException;
use App\Domain\Payroll\Models\OverrideRequest;
use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Models\PayrollRow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinalizePayrollService
{
    public function __construct(
        private CalculatePayrollService $calculatePayrollService
    ) {}

    public function execute(PayrollPeriod $period, User $actor): PayrollBatch
    {
        $this->validateState($period);
        $this->validateRole($actor);
        $this->validateNoPendingOverrides($period);

        return DB::transaction(function () use ($period, $actor) {
            $batch = $this->createBatch($period, $actor);
            $this->createRows($period, $batch);
            $this->freezePeriod($period, $actor);

            return $batch;
        });
    }

    private function validateState(PayrollPeriod $period): void
    {
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
                action: 'finalize payroll',
                requiredRole: 'owner',
            );
        }
    }

    private function validateNoPendingOverrides(PayrollPeriod $period): void
    {
        $pendingCount = OverrideRequest::whereHas('attendanceDecision', function ($query) use ($period) {
                $query->where('payroll_period_id', $period->id);
            })
            ->where(function ($query) {
                $query->where('status', 'PENDING')
                    ->orWhereNull('reviewed_at');
            })
            ->count();

        if ($pendingCount > 0) {
            throw new \DomainException(
                "Cannot finalize payroll. There are {$pendingCount} pending override request(s) that must be resolved first."
            );
        }
    }

    private function createBatch(PayrollPeriod $period, User $actor): PayrollBatch
    {
        return PayrollBatch::create([
            'company_id' => $period->company_id,
            'payroll_period_id' => $period->id,
            'total_amount' => 0,
            'finalized_by' => $actor->id,
            'finalized_at' => now(),
        ]);
    }

    private function createRows(PayrollPeriod $period, PayrollBatch $batch): void
    {
        // Use new calculation service with detailed breakdowns
        $this->calculatePayrollService->execute($batch);

        // Update batch total amount
        $totalAmount = PayrollRow::where('payroll_batch_id', $batch->id)
            ->sum('net_amount');

        $batch->total_amount = $totalAmount;
        $batch->save();
    }

    private function freezePeriod(PayrollPeriod $period, User $actor): void
    {
        $period->state = PayrollState::FINALIZED;
        $period->finalized_at = now();
        $period->finalized_by = $actor->id;
        $period->save();
    }
}
