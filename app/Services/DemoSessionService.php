<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Attendance\Enums\AttendanceSource;
use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Leave\Models\LeaveRecord;
use App\Domain\Leave\Models\LeaveType;
use App\Domain\Payroll\Enums\PayrollAdditionCode;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Models\OverrideRequest;
use App\Domain\Payroll\Models\PayrollAddition;
use App\Domain\Payroll\Models\PayrollDeductionRule;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Services\PreparePayrollService;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DemoSessionService
{
    /**
     * Create a fresh isolated demo sandbox.
     * Returns the owner User, Company, and plain-text password.
     *
     * @param  array{name?: string, email?: string, company_name?: string}  $params
     */
    public function createSandbox(array $params = []): array
    {
        $sandboxId = Str::uuid()->toString();
        $password = Str::random(10);
        $owner = null;
        $company = null;

        $ownerName    = $params['name']         ?? 'Owner Demo';
        $ownerEmail   = $params['email']        ?? "demo-owner-{$sandboxId}@payrollkami.app";
        $companyName  = $params['company_name'] ?? 'PT Demo PayrollKami';

        DB::transaction(function () use ($sandboxId, $password, $ownerName, $ownerEmail, $companyName, &$owner, &$company) {
            $company = Company::create([
                'name' => $companyName,
                'is_demo' => true,
                'is_onboarded' => true,
            ]);

            CompanyLocation::create([
                'company_id' => $company->id,
                'name' => 'Kantor Jakarta',
                'address' => 'Jl. Sudirman No. 1, Jakarta Pusat',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'geofence_radius_meters' => 100,
                'is_default' => true,
            ]);
            CompanyLocation::create([
                'company_id' => $company->id,
                'name' => 'Kantor Surabaya',
                'address' => 'Jl. Pemuda No. 15, Surabaya',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'geofence_radius_meters' => 150,
                'is_default' => false,
            ]);

            $deptEng = Department::create([
                'company_id' => $company->id,
                'name' => 'Engineering',
                'description' => 'Tim Teknologi dan Pengembangan',
            ]);
            $deptOps = Department::create([
                'company_id' => $company->id,
                'name' => 'Operasional',
                'description' => 'Tim Operasional dan Administrasi',
            ]);

            // Leave types are auto-created by CompanyObserver on company creation
            $paidLeaveType = LeaveType::where('company_id', $company->id)->where('code', 'annual')->first();
            $sickLeaveType = LeaveType::where('company_id', $company->id)->where('code', 'sick')->first();

            $owner = User::create([
                'company_id' => $company->id,
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => Hash::make($password),
            ]);
            $owner->assignRole('owner');

            $hr = User::create([
                'company_id' => $company->id,
                'name' => 'Budi Santoso',
                'email' => "demo-hr-{$sandboxId}@payrollkami.app",
                'password' => Hash::make('demo1234'),
            ]);
            $hr->assignRole('hr');

            $names = [
                'Andi Wijaya', 'Siti Rahayu', 'Bachtiar Yusuf', 'Dewi Kusuma',
                'Riko Pratama', 'Lina Hartati', 'Fajar Nugroho', 'Maya Sari',
                'Teguh Wibowo', 'Nadia Permata',
                'Ahmad Fauzi',   // Manager — 15jt, PPh21 significant
                'Sri Wahyuni',   // Senior Staff — 10jt, PPh21 visible
            ];
            $ptkpOptions = ['TK/0', 'TK/1', 'K/0', 'K/1', 'K/2'];
            $baseSalaries = [
                5000000, 6000000, 5500000, 7000000, 5200000,
                6500000, 5800000, 6200000, 5400000, 6800000,
                15000000, // Ahmad Fauzi — Manager
                10000000, // Sri Wahyuni — Senior Staff
            ];

            $employees = [];
            foreach ($names as $i => $name) {
                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $name,
                    'email' => "demo-emp{$i}-{$sandboxId}@payrollkami.app",
                    'password' => Hash::make('demo1234'),
                ]);
                $user->assignRole('employee');

                $emp = Employee::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'name' => $name,
                    'department_id' => $i < 6 ? $deptEng->id : $deptOps->id,
                    'join_date' => now()->subMonths(rand(6, 36))->startOfMonth(),
                    'status' => 'ACTIVE',
                    'ptkp_status' => $ptkpOptions[$i % count($ptkpOptions)],
                ]);

                EmployeeCompensation::create([
                    'employee_id' => $emp->id,
                    'base_salary' => $baseSalaries[$i],
                    'allowances' => $i >= 10 ? [
                        'transport'     => 1000000,
                        'meal'          => 500000,
                        'communication' => 500000,
                        'position'      => 2000000,
                    ] : [
                        'transport'     => 500000,
                        'meal'          => 300000,
                        'communication' => 200000,
                    ],
                    'effective_from' => $emp->join_date,
                    'effective_to' => null,
                    'notes' => 'Paket kompensasi awal',
                    'created_by' => $hr->id,
                ]);

                $employees[] = $emp;
            }

            foreach ([
                ['BPJS_KES', 'BPJS Kesehatan', 1.00, 4.00, 12000000],
                ['BPJS_TK_JHT', 'BPJS Ketenagakerjaan - JHT', 2.00, 3.70, 9559600],
                ['BPJS_TK_JP', 'BPJS Ketenagakerjaan - JP', 1.00, 2.00, 9559600],
            ] as [$code, $name, $empRate, $emplRate, $cap]) {
                PayrollDeductionRule::create([
                    'company_id' => $company->id,
                    'code' => $code,
                    'name' => $name,
                    'basis_type' => 'CAPPED_SALARY',
                    'employee_rate' => $empRate,
                    'employer_rate' => $emplRate,
                    'salary_cap' => $cap,
                    'effective_from' => now()->startOfYear(),
                    'notes' => "{$name} 2026",
                ]);
            }

            // Past month — completed payroll
            $lastMonth = now()->subMonth();
            $pastPeriod = PayrollPeriod::create([
                'company_id' => $company->id,
                'period_start' => $lastMonth->copy()->startOfMonth(),
                'period_end' => $lastMonth->copy()->endOfMonth(),
                'year' => $lastMonth->year,
                'month' => $lastMonth->month,
                'state' => PayrollState::DRAFT,
                'rule_version' => 'v1.0',
            ]);
            $this->seedAttendance($company->id, $employees, $lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth());
            app(PreparePayrollService::class)->execute($pastPeriod, $hr);
            $pastPeriod->update(['state' => PayrollState::FINALIZED]);

            // Current month — draft payroll
            $now = now();
            $currentPeriod = PayrollPeriod::create([
                'company_id' => $company->id,
                'period_start' => $now->copy()->startOfMonth(),
                'period_end' => $now->copy()->endOfMonth(),
                'year' => $now->year,
                'month' => $now->month,
                'state' => PayrollState::DRAFT,
                'rule_version' => 'v1.0',
            ]);
            if ($now->day > 1) {
                $this->seedAttendance($company->id, $employees, $now->copy()->startOfMonth(), $now->copy()->subDay());
            }

            LeaveRecord::create([
                'company_id' => $company->id,
                'employee_id' => $employees[0]->id,
                'date' => now()->subDays(8)->toDateString(),
                'leave_type_id' => $paidLeaveType?->id,
                'approved_by' => $hr->id,
            ]);
            LeaveRecord::create([
                'company_id' => $company->id,
                'employee_id' => $employees[2]->id,
                'date' => now()->subDays(3)->toDateString(),
                'leave_type_id' => $sickLeaveType?->id,
                'approved_by' => $hr->id,
            ]);

            PayrollAddition::create([
                'employee_id' => $employees[0]->id,
                'payroll_period_id' => $currentPeriod->id,
                'code' => PayrollAdditionCode::BONUS,
                'amount' => 1000000,
                'description' => 'Bonus kinerja Q1 2026',
                'created_by' => $hr->id,
            ]);
            PayrollAddition::create([
                'employee_id' => $employees[3]->id,
                'payroll_period_id' => $currentPeriod->id,
                'code' => PayrollAdditionCode::INCENTIVE,
                'amount' => 750000,
                'description' => 'Insentif penjualan',
                'created_by' => $hr->id,
            ]);

            app(PreparePayrollService::class)->execute($currentPeriod, $hr);

            $absentDecisions = AttendanceDecision::where('payroll_period_id', $currentPeriod->id)
                ->where('classification', AttendanceClassification::ABSENT)
                ->take(2)
                ->get();

            foreach ($absentDecisions as $decision) {
                OverrideRequest::create([
                    'attendance_decision_id' => $decision->id,
                    'old_classification' => $decision->classification,
                    'proposed_classification' => AttendanceClassification::PAID_LEAVE,
                    'reason' => 'Karyawan sudah mengajukan izin namun belum tercatat. Dokumen pendukung tersedia.',
                    'requested_by' => $hr->id,
                    'requested_at' => now()->subHours(2),
                    'status' => 'PENDING',
                ]);
            }
        });

        return [
            'company' => $company,
            'owner' => $owner,
            'password' => $password,
        ];
    }

    private function seedAttendance(int $companyId, array $employees, Carbon $from, Carbon $to): void
    {
        $current = $from->copy();
        while ($current->lte($to)) {
            if (!$current->isWeekend()) {
                foreach ($employees as $emp) {
                    if (rand(1, 100) <= 90) {
                        AttendanceRaw::create([
                            'company_id' => $companyId,
                            'employee_id' => $emp->id,
                            'date' => $current->toDateString(),
                            'clock_in' => $current->copy()->setTime(8, rand(0, 30), 0),
                            'clock_out' => $current->copy()->setTime(17, rand(0, 59), 0),
                            'source' => AttendanceSource::MACHINE,
                        ]);
                    }
                }
            }
            $current->addDay();
        }
    }
}
