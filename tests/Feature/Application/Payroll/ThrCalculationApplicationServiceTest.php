<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Payroll;

use App\Application\Payroll\DTOs\ThrCalculationRequest;
use App\Application\Payroll\Services\ThrCalculationApplicationService;
use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Models\PayrollAddition;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrCalculationApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ThrCalculationApplicationService $service;
    private Company $company;
    private Employee $employee;
    private PayrollPeriod $period;
    private EmployeeCompensation $compensation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ThrCalculationApplicationService::class);
        $this->setupTestData();
    }

    public function test_calculate_for_employee_success(): void
    {
        $request = new ThrCalculationRequest(
            employeeId: $this->employee->id,
            periodId: $this->period->id,
            companyId: $this->company->id,
            employeeType: 'permanent'
        );

        $result = $this->service->calculateForEmployee($request);

        $this->assertTrue($result->isEligible());
        $this->assertEquals(5000000, $result->thrAmount);
        $this->assertStringContainsString('THR penuh', $result->notes);
    }

    public function test_calculate_for_employee_with_invalid_employee(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Employee not found or has no active compensation');

        $request = new ThrCalculationRequest(
            employeeId: 999999,
            periodId: $this->period->id,
            companyId: $this->company->id
        );

        $this->service->calculateForEmployee($request);
    }

    public function test_calculate_for_employee_with_invalid_period(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payroll period not found or does not belong to company');

        $request = new ThrCalculationRequest(
            employeeId: $this->employee->id,
            periodId: 999999,
            companyId: $this->company->id
        );

        $this->service->calculateForEmployee($request);
    }

    public function test_calculate_and_create_thr_success(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $request = new ThrCalculationRequest(
            employeeId: $this->employee->id,
            periodId: $this->period->id,
            companyId: $this->company->id,
            employeeType: 'permanent'
        );

        $result = $this->service->calculateAndCreateThr($request, $user->id);

        $this->assertArrayHasKey('addition', $result);
        $this->assertArrayHasKey('calculation_result', $result);

        $addition = $result['addition'];
        $this->assertInstanceOf(PayrollAddition::class, $addition);
        $this->assertEquals('THR', $addition->code);
        $this->assertEquals(5000000, $addition->amount);
        $this->assertEquals($this->employee->id, $addition->employee_id);
        $this->assertEquals($this->period->id, $addition->payroll_period_id);
        $this->assertEquals($user->id, $addition->created_by);

        // Verify the record is in database
        $this->assertDatabaseHas('payroll_additions', [
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->period->id,
            'code' => 'THR',
            'amount' => 5000000,
            'created_by' => $user->id,
        ]);
    }

    public function test_calculate_and_create_thr_throws_exception_for_existing_thr(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        // Create existing THR
        PayrollAddition::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->period->id,
            'code' => 'THR',
            'amount' => 1000000,
            'created_by' => $user->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('THR already exists for this employee in this period');

        $request = new ThrCalculationRequest(
            employeeId: $this->employee->id,
            periodId: $this->period->id,
            companyId: $this->company->id
        );

        $this->service->calculateAndCreateThr($request, $user->id);
    }

    public function test_get_calculation_preview_success(): void
    {
        $request = new ThrCalculationRequest(
            employeeId: $this->employee->id,
            periodId: $this->period->id,
            companyId: $this->company->id,
            employeeType: 'contract'
        );

        $preview = $this->service->getCalculationPreview($request);

        $this->assertTrue($preview['success']);
        $this->assertArrayHasKey('result', $preview);
        $this->assertArrayHasKey('existing_thr', $preview);
        $this->assertNull($preview['existing_thr']);
    }

    public function test_get_calculation_preview_with_existing_thr(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $existingThr = PayrollAddition::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->period->id,
            'code' => 'THR',
            'amount' => 2000000,
            'created_by' => $user->id,
        ]);

        $request = new ThrCalculationRequest(
            employeeId: $this->employee->id,
            periodId: $this->period->id,
            companyId: $this->company->id
        );

        $preview = $this->service->getCalculationPreview($request);

        $this->assertTrue($preview['success']);
        $this->assertNotNull($preview['existing_thr']);
        $this->assertEquals($existingThr->id, $preview['existing_thr']->id);
    }

    public function test_get_calculation_preview_with_error(): void
    {
        $request = new ThrCalculationRequest(
            employeeId: 999999, // Invalid employee
            periodId: $this->period->id,
            companyId: $this->company->id
        );

        $preview = $this->service->getCalculationPreview($request);

        $this->assertFalse($preview['success']);
        $this->assertArrayHasKey('error', $preview);
        $this->assertStringContainsString('Employee not found', $preview['error']);
    }

    private function setupTestData(): void
    {
        $this->company = Company::factory()->create();
        
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'join_date' => Carbon::parse('2023-01-01'),
            'status' => 'ACTIVE',
        ]);

        $this->period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'period_start' => Carbon::parse('2023-12-01'),
            'period_end' => Carbon::parse('2023-12-31'),
        ]);

        $this->compensation = EmployeeCompensation::factory()->create([
            'employee_id' => $this->employee->id,
            'base_salary' => 5000000,
            'effective_from' => Carbon::parse('2023-01-01'),
            'effective_to' => null, // Active compensation
        ]);
    }
}