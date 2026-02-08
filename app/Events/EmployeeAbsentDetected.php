<?php

namespace App\Events;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeAbsentDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public string $date,
        public Company $company
    ) {
    }
}
