<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Payroll;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Models\OverrideRequest;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Services\FinalizePayrollService;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalizePayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinalizePayrollService $service;
    private Company $company;
    private PayrollPeriod $period;
    private User $owner;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FinalizePayrollService::class);
        $this->setupTestData();
    }

    public function test_finalize_payroll_succeeds_when_no_pending_overrides(): void
    {
        // Given: A period in REVIEW state with no pending overrides
        $this->period->state = PayrollState::REVIEW;
        $this->period->save();

        // When: Finalizing the payroll
        $batch = $this->service->execute($this->period, $this->owner);

        // Then: Finalization succeeds
        $this->assertNotNull($batch);
        $this->assertEquals($this->period->id, $batch->payroll_period_id);
        $this->assertEquals($this->owner->id, $batch->finalized_by);

        // And: Period state is updated
        $this->period->refresh();
        $this->assertEquals(PayrollState::FINALIZED, $this->period->state);
        $this->assertNotNull($this->period->finalized_at);
        $this->assertEquals($this->owner->id, $this->period->finalized_by);
    }

    public function test_finalize_payroll_fails_when_pending_overrides_exist(): void
    {
        // Given: A period in REVIEW state
        $this->period->state = PayrollState::REVIEW;
        $this->period->save();

        // And: An attendance decision for this period
        $attendanceDecision = AttendanceDecision::factory()->create([
            'payroll_period_id' => $this->period->id,
            'employee_id' => $this->employee->id,
            'classification' => AttendanceClassification::ABSENT,
            'payable' => false,
        ]);

        // And: A pending override request
        OverrideRequest::create([
            'attendance_decision_id' => $attendanceDecision->id,
            'old_classification' => AttendanceClassification::ABSENT,
            'proposed_classification' => AttendanceClassification::ATTEND,
            'reason' => 'Employee was present but system marked absent',
            'requested_by' => $this->employee->user_id,
            'requested_at' => now(),
            'status' => 'PENDING',
        ]);

        // When: Attempting to finalize the payroll
        // Then: Exception is thrown
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot finalize payroll. There are 1 pending override request(s) that must be resolved first.');

        $this->service->execute($this->period, $this->owner);
    }

    public function test_finalize_payroll_fails_when_override_has_null_reviewed_at(): void
    {
        // Given: A period in REVIEW state
        $this->period->state = PayrollState::REVIEW;
        $this->period->save();

        // And: An attendance decision
        $attendanceDecision = AttendanceDecision::factory()->create([
            'payroll_period_id' => $this->period->id,
            'employee_id' => $this->employee->id,
        ]);

        // And: An override request with null reviewed_at (even if status is not PENDING)
        OverrideRequest::create([
            'attendance_decision_id' => $attendanceDecision->id,
            'old_classification' => AttendanceClassification::ABSENT,
            'proposed_classification' => AttendanceClassification::ATTEND,
            'reason' => 'Test reason',
            'requested_by' => $this->employee->user_id,
            'requested_at' => now(),
            'status' => 'APPROVED', // Status is approved but reviewed_at is null
            'reviewed_at' => null,  // This should still block finalization
        ]);

        // When: Attempting to finalize
        // Then: Exception is thrown
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot finalize payroll. There are 1 pending override request(s) that must be resolved first.');

        $this->service->execute($this->period, $this->owner);
    }

    public function test_finalize_payroll_succeeds_when_all_overrides_are_reviewed(): void
    {
        // Given: A period in REVIEW state
        $this->period->state = PayrollState::REVIEW;
        $this->period->save();

        // And: An attendance decision
        $attendanceDecision = AttendanceDecision::factory()->create([
            'payroll_period_id' => $this->period->id,
            'employee_id' => $this->employee->id,
        ]);

        // And: An override request that has been reviewed
        OverrideRequest::create([
            'attendance_decision_id' => $attendanceDecision->id,
            'old_classification' => AttendanceClassification::ABSENT,
            'proposed_classification' => AttendanceClassification::ATTEND,
            'reason' => 'Test reason',
            'requested_by' => $this->employee->user_id,
            'requested_at' => now(),
            'status' => 'APPROVED',
            'reviewed_by' => $this->owner->id,
            'reviewed_at' => now(),
            'review_note' => 'Approved by owner',
        ]);

        // When: Finalizing the payroll
        $batch = $this->service->execute($this->period, $this->owner);

        // Then: Finalization succeeds
        $this->assertNotNull($batch);
        $this->assertEquals(PayrollState::FINALIZED, $this->period->fresh()->state);
    }

    public function test_finalize_payroll_fails_when_multiple_pending_overrides(): void
    {
        // Given: A period in REVIEW state
        $this->period->state = PayrollState::REVIEW;
        $this->period->save();

        // And: Multiple attendance decisions
        $attendanceDecision1 = AttendanceDecision::factory()->create([
            'payroll_period_id' => $this->period->id,
            'employee_id' => $this->employee->id,
        ]);

        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        $attendanceDecision2 = AttendanceDecision::factory()->create([
            'payroll_period_id' => $this->period->id,
            'employee_id' => $employee2->id,
        ]);

        // And: Multiple pending override requests
        OverrideRequest::create([
            'attendance_decision_id' => $attendanceDecision1->id,
            'old_classification' => AttendanceClassification::ABSENT,
            'proposed_classification' => AttendanceClassification::ATTEND,
            'reason' => 'First override',
            'requested_by' => $this->employee->user_id,
            'requested_at' => now(),
            'status' => 'PENDING',
        ]);

        OverrideRequest::create([
            'attendance_decision_id' => $attendanceDecision2->id,
            'old_classification' => AttendanceClassification::LATE,
            'proposed_classification' => AttendanceClassification::ATTEND,
            'reason' => 'Second override',
            'requested_by' => $employee2->user_id,
            'requested_at' => now(),
            'status' => 'PENDING',
        ]);

        // When: Attempting to finalize
        // Then: Exception mentions multiple requests
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot finalize payroll. There are 2 pending override request(s) that must be resolved first.');

        $this->service->execute($this->period, $this->owner);
    }

    public function test_finalize_payroll_fails_when_period_not_in_review_state(): void
    {
        // Given: A period in DRAFT state (not REVIEW)
        $this->period->state = PayrollState::DRAFT;
        $this->period->save();

        // When: Attempting to finalize
        // Then: Exception is thrown for wrong state
        $this->expectException(\App\Domain\Payroll\Exceptions\InvalidPayrollStateException::class);

        $this->service->execute($this->period, $this->owner);
    }

    public function test_finalize_payroll_fails_when_user_not_owner(): void
    {
        // Given: A period in REVIEW state with no pending overrides
        $this->period->state = PayrollState::REVIEW;
        $this->period->save();

        // And: A non-owner user
        $nonOwner = User::factory()->create(['company_id' => $this->company->id]);

        // When: Attempting to finalize
        // Then: Exception is thrown for unauthorized user
        $this->expectException(\App\Domain\Payroll\Exceptions\UnauthorizedPayrollActionException::class);

        $this->service->execute($this->period, $nonOwner);
    }

    private function setupTestData(): void
    {
        $this->company = Company::factory()->create();
        
        $this->owner = User::factory()->owner()->create([
            'company_id' => $this->company->id,
        ]);
        // Assign owner role (assuming you have a role system)
        // $this->owner->assignRole('owner'); // Uncomment when role system is implemented

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => User::factory()->create(['company_id' => $this->company->id])->id,
        ]);

        $this->period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'period_start' => Carbon::parse('2024-01-01'),
            'period_end' => Carbon::parse('2024-01-31'),
            'state' => PayrollState::DRAFT,
        ]);
    }
}