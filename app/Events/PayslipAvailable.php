<?php

namespace App\Events;

use App\Domain\Payroll\Models\PayrollRow;
use App\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayslipAvailable
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PayrollRow $payrollRow,
        public Employee $employee
    ) {
    }
}
