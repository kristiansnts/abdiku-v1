<?php

namespace App\Helpers;

use App\Domain\Attendance\Models\AttendanceRequest;
use App\Domain\Payroll\Models\OverrideRequest;
use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Models\PayrollRow;

class FilamentUrlHelper
{
    /**
     * Generate URL for attendance request view page
     */
    public static function attendanceRequestUrl(AttendanceRequest $request): string
    {
        return route('filament.admin.resources.attendance-requests.view', ['record' => $request->id]);
    }

    /**
     * Generate URL for payroll period view page
     */
    public static function payrollPeriodUrl(PayrollPeriod $period): string
    {
        return route('filament.admin.resources.payroll-periods.view', ['record' => $period->id]);
    }

    /**
     * Generate URL for override request view page
     */
    public static function overrideRequestUrl(OverrideRequest $request): string
    {
        return route('filament.admin.resources.override-requests.view', ['record' => $request->id]);
    }

    /**
     * Generate URL for payroll batch view page
     */
    public static function payrollBatchUrl(PayrollBatch $batch): string
    {
        return route('filament.admin.resources.payroll-batches.view', ['record' => $batch->id]);
    }

    /**
     * Generate URL for payslip (payroll row) view page
     */
    public static function payslipUrl(PayrollRow $row): string
    {
        return route('filament.admin.resources.payroll-rows.view', ['record' => $row->id]);
    }
}
