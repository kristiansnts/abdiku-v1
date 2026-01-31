<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Leave\Enums\LeaveType;
use App\Domain\Leave\Models\LeaveRecord;
use App\Domain\Payroll\Enums\PayrollAdditionCode;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Models\PayrollAddition;
use App\Domain\Payroll\Models\PayrollDeductionRule;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating role...');

        $this->call([
            RoleSeeder::class,
        ]);

        $this->command->info('Creating company...');

        // Create company
        $company = Company::create([
            'name' => 'PT Demo Indonesia',
        ]);

        $this->command->info('Creating users and employees...');

        // Create users with roles
        $owner = User::create([
            'company_id' => $company->id,
            'name' => 'Owner Demo',
            'email' => 'owner@demo.test',
            'password' => Hash::make('password'),
        ]);
        if (\Spatie\Permission\Models\Role::where('name', 'owner')->exists()) {
            $owner->assignRole('owner');
        }

        $hr = User::create([
            'company_id' => $company->id,
            'name' => 'HR Manager',
            'email' => 'hr@demo.test',
            'password' => Hash::make('password'),
        ]);
        if (\Spatie\Permission\Models\Role::where('name', 'hr')->exists()) {
            $hr->assignRole('hr');
        }

        // Create employee users and corresponding Employee records
        $employees = [];
        $baseSalaries = [5000000, 6000000, 5500000, 7000000, 5000000, 6500000, 5800000, 6200000, 5400000, 6800000];

        for ($i = 1; $i <= 10; $i++) {
            $employeeName = fake()->name();

            $user = User::create([
                'company_id' => $company->id,
                'name' => $employeeName,
                'email' => "employee{$i}@demo.test",
                'password' => Hash::make('password'),
            ]);
            if (\Spatie\Permission\Models\Role::where('name', 'employee')->exists()) {
                $user->assignRole('employee');
            }

            // Create Employee record linked to user
            $employee = Employee::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'name' => $employeeName,
                'join_date' => now()->subMonths(rand(6, 36))->startOfMonth(),
                'status' => 'ACTIVE',
            ]);

            // Create employee compensation
            EmployeeCompensation::create([
                'employee_id' => $employee->id,
                'base_salary' => $baseSalaries[$i - 1],
                'allowances' => [
                    'transport' => 500000,
                    'meal' => 300000,
                    'communication' => 200000,
                ],
                'effective_from' => $employee->join_date,
                'effective_to' => null,
                'notes' => 'Initial compensation package',
                'created_by' => $hr->id,
            ]);

            $employees[] = $employee;
        }

        $this->command->info('Creating BPJS deduction rules...');

        // Create deduction rules (BPJS)
        PayrollDeductionRule::create([
            'company_id' => $company->id,
            'code' => 'BPJS_KES',
            'name' => 'BPJS Kesehatan',
            'basis_type' => 'CAPPED_SALARY',
            'employee_rate' => 1.00,
            'employer_rate' => 4.00,
            'salary_cap' => 12000000,
            'effective_from' => now()->startOfYear(),
            'notes' => 'BPJS Kesehatan 2026',
        ]);

        PayrollDeductionRule::create([
            'company_id' => $company->id,
            'code' => 'BPJS_TK_JHT',
            'name' => 'BPJS Ketenagakerjaan - JHT',
            'basis_type' => 'CAPPED_SALARY',
            'employee_rate' => 2.00,
            'employer_rate' => 3.70,
            'salary_cap' => 9559600,
            'effective_from' => now()->startOfYear(),
            'notes' => 'BPJS TK JHT 2026',
        ]);

        PayrollDeductionRule::create([
            'company_id' => $company->id,
            'code' => 'BPJS_TK_JP',
            'name' => 'BPJS Ketenagakerjaan - JP',
            'basis_type' => 'CAPPED_SALARY',
            'employee_rate' => 1.00,
            'employer_rate' => 2.00,
            'salary_cap' => 9559600,
            'effective_from' => now()->startOfYear(),
            'notes' => 'BPJS TK JP 2026',
        ]);

        $this->command->info('Creating payroll period...');

        // Create payroll period (current month)
        $period = PayrollPeriod::create([
            'company_id' => $company->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'state' => PayrollState::DRAFT,
            'rule_version' => 'v1.0',
        ]);

        $this->command->info('Adding payroll additions (bonuses)...');

        // Add some payroll additions (bonuses for top performers)
        PayrollAddition::create([
            'employee_id' => $employees[0]->id,
            'payroll_period_id' => $period->id,
            'code' => PayrollAdditionCode::BONUS,
            'amount' => 1000000,
            'description' => 'Performance bonus - Q4 2025',
            'created_by' => $hr->id,
        ]);

        PayrollAddition::create([
            'employee_id' => $employees[3]->id,
            'payroll_period_id' => $period->id,
            'code' => PayrollAdditionCode::INCENTIVE,
            'amount' => 750000,
            'description' => 'Sales incentive',
            'created_by' => $hr->id,
        ]);

        $this->command->info('Creating attendance records...');

        // Create attendance records for the past 15 days
        $startDate = now()->subDays(15);
        for ($day = 0; $day < 15; $day++) {
            $date = $startDate->copy()->addDays($day);

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($employees as $employee) {
                // 90% attendance rate
                if (rand(1, 100) <= 90) {
                    $clockIn = $date->copy()->setTime(8, rand(0, 30), 0);
                    $clockOut = $date->copy()->setTime(17, rand(0, 59), 0);

                    AttendanceRaw::create([
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'source' => AttendanceSource::MACHINE,
                    ]);
                }
            }
        }

        $this->command->info('Creating leave records...');

        // Create some leave records
        $leaveDate1 = now()->subDays(10);
        LeaveRecord::create([
            'company_id' => $company->id,
            'employee_id' => $employees[0]->id,
            'date' => $leaveDate1->toDateString(),
            'leave_type' => LeaveType::PAID,
            'approved_by' => $hr->id,
        ]);

        $leaveDate2 = now()->subDays(5);
        LeaveRecord::create([
            'company_id' => $company->id,
            'employee_id' => $employees[1]->id,
            'date' => $leaveDate2->toDateString(),
            'leave_type' => LeaveType::SICK_PAID,
            'approved_by' => $hr->id,
        ]);

        // First, prepare payroll to generate attendance decisions
        $this->command->info('Preparing payroll to generate attendance decisions...');
        app(\App\Domain\Payroll\Services\PreparePayrollService::class)->execute($period, $hr);

        // Create some override requests
        $this->command->info('Creating override requests...');
        $decisions = \App\Domain\Attendance\Models\AttendanceDecision::where('payroll_period_id', $period->id)
            ->where('classification', \App\Domain\Attendance\Enums\AttendanceClassification::ABSENT)
            ->take(3)
            ->get();

        foreach ($decisions as $index => $decision) {
            \App\Domain\Payroll\Models\OverrideRequest::create([
                'attendance_decision_id' => $decision->id,
                'old_classification' => $decision->classification,
                'proposed_classification' => \App\Domain\Attendance\Enums\AttendanceClassification::PAID_LEAVE,
                'reason' => 'Employee was on approved leave but forgot to submit leave request. Supporting documents attached.',
                'requested_by' => $hr->id,
                'requested_at' => now()->subHours(3 - $index),
                'status' => 'PENDING',
            ]);
        }

        // Create one approved override request (for history)
        if ($decisions->count() > 3) {
            $decision = \App\Domain\Attendance\Models\AttendanceDecision::where('payroll_period_id', $period->id)
                ->where('classification', \App\Domain\Attendance\Enums\AttendanceClassification::LATE)
                ->first();

            if ($decision) {
                \App\Domain\Payroll\Models\OverrideRequest::create([
                    'attendance_decision_id' => $decision->id,
                    'old_classification' => $decision->classification,
                    'proposed_classification' => \App\Domain\Attendance\Enums\AttendanceClassification::ATTEND,
                    'reason' => 'Clock-in machine malfunction. Employee arrived on time but system recorded late entry.',
                    'requested_by' => $hr->id,
                    'requested_at' => now()->subDay(),
                    'status' => 'APPROVED',
                    'reviewed_by' => $owner->id,
                    'reviewed_at' => now()->subHours(20),
                    'review_note' => 'Approved. Machine logs verified.',
                ]);
            }
        }

        $this->command->newLine();
        $this->command->info('✅ Dummy data created successfully!');
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('📋 LOGIN CREDENTIALS');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('Owner: owner@demo.test (password: password)');
        $this->command->info('HR: hr@demo.test (password: password)');
        $this->command->info('Employees: employee1@demo.test - employee10@demo.test (password: password)');
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('📊 DATA SUMMARY');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('Company: ' . $company->name);
        $this->command->info('Employees: ' . Employee::count());
        $this->command->info('Employee Compensations: ' . EmployeeCompensation::count());
        $this->command->info('Deduction Rules: ' . PayrollDeductionRule::count());
        $this->command->info('Payroll Additions: ' . PayrollAddition::count());
        $this->command->newLine();
        $this->command->info('Payroll Period: ' . $period->period_start->format('Y-m-d') . ' to ' . $period->period_end->format('Y-m-d'));
        $this->command->info('Attendance Records: ' . AttendanceRaw::count());
        $this->command->info('Leave Records: ' . LeaveRecord::count());
        $this->command->info('Attendance Decisions: ' . \App\Domain\Attendance\Models\AttendanceDecision::count());
        $this->command->info('Pending Override Requests: ' . \App\Domain\Payroll\Models\OverrideRequest::where('status', 'PENDING')->count());
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('🔔 You should see a badge on "Override Requests" menu!');
        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}
