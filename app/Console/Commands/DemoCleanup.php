<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoCleanup extends Command
{
    protected $signature = 'demo:cleanup {--older-than=0 : Only delete demo companies older than N hours}';
    protected $description = 'Delete all demo sandbox companies and their associated data';

    public function handle(): int
    {
        $olderThan = (int) $this->option('older-than');

        $query = Company::where('is_demo', true);
        if ($olderThan > 0) {
            $query->where('created_at', '<', now()->subHours($olderThan));
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->info('No demo companies found.');
            return self::SUCCESS;
        }

        $this->info("Found {$companies->count()} demo company(ies) to delete.");

        if (!$this->option('no-interaction') && !$this->confirm('Proceed with deletion?', true)) {
            $this->warn('Aborted.');
            return self::FAILURE;
        }

        $deleted = 0;
        foreach ($companies as $company) {
            DB::transaction(function () use ($company) {
                $userIds = $company->users()->pluck('id');
                $employeeIds = $company->employees()->pluck('id');

                DB::table('model_has_roles')->whereIn('model_id', $userIds)->delete();
                DB::table('employee_compensations')->whereIn('employee_id', $employeeIds)->delete();
                DB::table('leave_balances')->whereIn('employee_id', $employeeIds)->delete();

                $periodIds = DB::table('payroll_periods')->where('company_id', $company->id)->pluck('id');
                $batchIds = DB::table('payroll_batches')->whereIn('payroll_period_id', $periodIds)->pluck('id');
                $rowIds = DB::table('payroll_rows')->whereIn('payroll_batch_id', $batchIds)->pluck('id');

                DB::table('payroll_additions')->whereIn('payroll_period_id', $periodIds)->delete();
                DB::table('payroll_row_additions')->whereIn('payroll_row_id', $rowIds)->delete();
                DB::table('payroll_row_deductions')->whereIn('payroll_row_id', $rowIds)->delete();
                DB::table('payroll_rows')->whereIn('payroll_batch_id', $batchIds)->delete();
                DB::table('payroll_batches')->whereIn('id', $batchIds)->delete();

                $decisionIds = DB::table('attendance_decisions')->whereIn('payroll_period_id', $periodIds)->pluck('id');
                DB::table('override_requests')->whereIn('attendance_decision_id', $decisionIds)->delete();
                DB::table('attendance_decisions')->whereIn('payroll_period_id', $periodIds)->delete();

                DB::table('payroll_deduction_rules')->where('company_id', $company->id)->delete();
                DB::table('payroll_periods')->where('company_id', $company->id)->delete();
                DB::table('attendance_raw')->where('company_id', $company->id)->delete();
                DB::table('leave_records')->where('company_id', $company->id)->delete();
                DB::table('leave_types')->where('company_id', $company->id)->delete();
                DB::table('employees')->where('company_id', $company->id)->delete();
                DB::table('users')->where('company_id', $company->id)->delete();
                DB::table('company_locations')->where('company_id', $company->id)->delete();
                DB::table('departments')->where('company_id', $company->id)->delete();

                $company->delete();
            });
            $deleted++;
            $this->line("  ✓ Deleted: {$company->name} (ID: {$company->id})");
        }

        $this->info("✅ Deleted {$deleted} demo company(ies) and all associated data.");
        return self::SUCCESS;
    }
}

