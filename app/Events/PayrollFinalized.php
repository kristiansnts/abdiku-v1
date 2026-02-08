<?php

namespace App\Events;

use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PayrollPeriod $payrollPeriod,
        public PayrollBatch $payrollBatch,
        public User $finalizedBy
    ) {
    }
}
