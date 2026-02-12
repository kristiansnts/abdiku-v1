<?php

namespace App\Observers;

use App\Domain\Leave\Models\LeaveBalance;
use App\Domain\Leave\Models\LeaveType;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     * Automatically create default leave balances for the new employee.
     */
    public function created(Employee $employee): void
    {
        $currentYear = Carbon::now()->year;

        // Find the annual leave type for this company
        $annualLeaveType = LeaveType::where('company_id', $employee->company_id)
            ->where('code', 'annual')
            ->first();

        if ($annualLeaveType) {
            LeaveBalance::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $annualLeaveType->id,
                'year' => $currentYear,
                'balance' => 12, // Default 12 days for annual leave
            ]);
        }
    }
}

