<?php

namespace Tests\Feature\Domain\Payroll;

use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Domain\Attendance\Models\WorkPattern;
use App\Domain\Attendance\Models\EmployeeWorkAssignment;
use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Services\CalculatePayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompliancePayrollTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private CalculatePayrollService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->service = app(CalculatePayrollService::class);
    }

    /** @test */
    public function it_calculates_tax_based_on_ter_2024_categories()
    {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'ptkp_status' => 'TK/0',
            'status' => 'ACTIVE'
        ]);

        EmployeeCompensation::factory()->create([
            'employee_id' => $employee->id,
            'base_salary' => 10000000,
            'allowances' => [],
            'effective_from' => now()->subMonth()
        ]);

        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth()
        ]);

        $batch = PayrollBatch::create([
            'company_id' => $this->company->id,
            'payroll_period_id' => $period->id,
            'total_amount' => 0,
            'finalized_by' => $this->admin->id,
            'finalized_at' => now()
        ]);

        $this->createFullAttendance($employee, $period);

        $this->service->execute($batch);

        $row = $employee->payrollRows()->where('payroll_batch_id', $batch->id)->first();

        // 10,000,000 * 2.0% TER Category A = 200,000
        $this->assertEquals(200000, (float) $row->tax_amount);
        $this->assertEquals(9800000, (float) $row->net_amount);
    }

    /** @test */
    public function it_respects_6_day_work_patterns_for_proration()
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id, 'status' => 'ACTIVE']);
        
        $pattern = WorkPattern::factory()->create([
            'company_id' => $this->company->id,
            'working_days' => [1, 2, 3, 4, 5, 6] 
        ]);

        EmployeeWorkAssignment::factory()->create([
            'employee_id' => $employee->id,
            'work_pattern_id' => $pattern->id,
            'effective_from' => '2024-01-01'
        ]);

        EmployeeCompensation::factory()->create([
            'employee_id' => $employee->id,
            'base_salary' => 6000000,
            'allowances' => []
        ]);

        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'period_start' => '2024-02-01',
            'period_end' => '2024-02-29'
        ]);

        // Feb 2024 has 25 Mon-Sat days. We pay for 10 days (exactly 40%).
        $this->createPartialAttendance($employee, $period, 10);

        $batch = PayrollBatch::create([
            'company_id' => $this->company->id,
            'payroll_period_id' => $period->id,
            'total_amount' => 0,
            'finalized_by' => $this->admin->id,
            'finalized_at' => now()
        ]);
        
        $this->service->execute($batch);

        $row = $employee->payrollRows()->first();
        
        // 40% of 6M = 2.4M
        $this->assertEquals(2400000, (float) $row->gross_amount);
    }

    private function createFullAttendance($employee, $period)
    {
        $start = $period->period_start->copy();
        while ($start <= $period->period_end) {
            if ($start->isWeekday()) {
                AttendanceDecision::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'date' => $start->toDateString(),
                    'payable' => true,
                    'classification' => 'ATTEND',
                    'deduction_type' => 'NONE',
                    'rule_version' => 'v1',
                    'decided_at' => now()
                ]);
            }
            $start->addDay();
        }
    }

    private function createPartialAttendance($employee, $period, $count)
    {
        for ($i = 0; $i < $count; $i++) {
            AttendanceDecision::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'date' => $period->period_start->copy()->addDays($i)->toDateString(),
                'payable' => true,
                'classification' => 'ATTEND',
                'deduction_type' => 'NONE',
                'rule_version' => 'v1',
                'decided_at' => now()
            ]);
        }
    }
}
