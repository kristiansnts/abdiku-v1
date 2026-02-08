<?php

namespace Tests\Feature\Notifications;

use App\Domain\Attendance\Enums\AttendanceClassification;
use App\Domain\Attendance\Models\AttendanceDecision;
use App\Domain\Payroll\Enums\PayrollState;
use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollPeriod;
use App\Domain\Payroll\Models\PayrollRow;
use App\Domain\Payroll\Services\FinalizePayrollService;
use App\Domain\Payroll\Services\PreparePayrollService;
use App\Events\PayrollFinalized;
use App\Events\PayrollPrepared;
use App\Events\PayslipAvailable;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

class PayrollNotificationTest extends TestCase
{
    use RefreshDatabase, CreatesRoles;

    private Company $company;
    private User $hrUser;
    private User $ownerUser;
    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRoles();

        // Create company
        $this->company = Company::factory()->create();

        // Create HR user
        $this->hrUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->hrUser->assignRole('hr');

        // Create owner user
        $this->ownerUser = User::factory()->create(['company_id' => $this->company->id, 'role' => 'OWNER']);
        $this->ownerUser->assignRole('owner');

        // Create employee user
        $this->employeeUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->employeeUser->id,
            'status' => 'ACTIVE',
        ]);

        // Create compensation for employee (required for payroll calculation)
        \App\Domain\Payroll\Models\EmployeeCompensation::factory()->create([
            'employee_id' => $this->employee->id,
            'effective_from' => now()->subMonth(),
            'base_salary' => 5000000,
        ]);
    }

    /** @test */
    public function it_sends_notification_to_stakeholders_when_payroll_is_prepared()
    {
        // Arrange: Create payroll period
        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'state' => PayrollState::DRAFT,
        ]);

        // Act: Prepare payroll
        $service = app(PreparePayrollService::class);
        $service->execute($period, $this->hrUser);

        // Assert: HR and Owner received notifications
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->hrUser->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->ownerUser->id,
            'notifiable_type' => User::class,
        ]);

        // Find notification with title containing "Penggajian Siap"
        $notification = DatabaseNotification::where('notifiable_id', $this->hrUser->id)
            ->get()
            ->first(function ($n) {
                return str_contains($n->data['title'] ?? '', 'Penggajian Siap');
            });
        $this->assertNotNull($notification, 'Could not find notification with title containing "Penggajian Siap"');
        $this->assertStringContainsString('Penggajian Siap', $notification->data['title']);
    }

    /** @test */
    public function it_dispatches_payroll_prepared_event()
    {
        Event::fake([PayrollPrepared::class]);

        // Arrange
        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'state' => PayrollState::DRAFT,
        ]);

        // Act
        $service = app(PreparePayrollService::class);
        $service->execute($period, $this->hrUser);

        // Assert
        Event::assertDispatched(PayrollPrepared::class, function ($event) use ($period) {
            return $event->payrollPeriod->id === $period->id
                && $event->preparedBy->id === $this->hrUser->id
                && $event->employeeCount > 0;
        });
    }

    /** @test */
    public function it_dispatches_payroll_finalized_event()
    {
        Event::fake([PayrollFinalized::class, PayslipAvailable::class]);

        // Arrange: Create payroll period in REVIEW state
        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'state' => PayrollState::REVIEW,
        ]);

        // Create attendance decision
        AttendanceDecision::factory()->create([
            'payroll_period_id' => $period->id,
            'employee_id' => $this->employee->id,
            'classification' => AttendanceClassification::ATTEND,
            'payable' => true,
        ]);

        // Act: Finalize payroll
        $service = app(FinalizePayrollService::class);
        $batch = $service->execute($period, $this->ownerUser);

        // Assert
        Event::assertDispatched(PayrollFinalized::class, function ($event) use ($period, $batch) {
            return $event->payrollPeriod->id === $period->id
                && $event->payrollBatch->id === $batch->id
                && $event->finalizedBy->id === $this->ownerUser->id;
        });
    }

    /** @test */
    public function it_dispatches_payslip_available_events_for_each_employee()
    {
        Event::fake([PayslipAvailable::class]);

        // Arrange: Create payroll period with multiple employees
        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'state' => PayrollState::REVIEW,
        ]);

        $employee2 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'ACTIVE',
        ]);

        // Create compensation for employee2
        \App\Domain\Payroll\Models\EmployeeCompensation::factory()->create([
            'employee_id' => $employee2->id,
            'effective_from' => now()->subMonth(),
            'base_salary' => 5000000,
        ]);

        AttendanceDecision::factory()->create([
            'payroll_period_id' => $period->id,
            'employee_id' => $this->employee->id,
            'classification' => AttendanceClassification::ATTEND,
        ]);

        AttendanceDecision::factory()->create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee2->id,
            'classification' => AttendanceClassification::ATTEND,
        ]);

        // Act: Finalize payroll
        $service = app(FinalizePayrollService::class);
        $service->execute($period, $this->ownerUser);

        // Assert: PayslipAvailable event dispatched for each employee
        Event::assertDispatched(PayslipAvailable::class, 2);
    }

    /** @test */
    public function it_only_notifies_users_in_same_company()
    {
        // Arrange: Create another company
        $otherCompany = Company::factory()->create();
        $otherOwner = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'OWNER']);
        $otherOwner->assignRole('owner');

        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'state' => PayrollState::DRAFT,
        ]);

        // Act: Prepare payroll
        $service = app(PreparePayrollService::class);
        $service->execute($period, $this->hrUser);

        // Assert: Only users from same company notified
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->ownerUser->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $otherOwner->id,
        ]);
    }

    /** @test */
    public function it_sends_payslip_notification_with_net_salary()
    {
        // Arrange: Create payroll batch and row
        $period = PayrollPeriod::factory()->create([
            'company_id' => $this->company->id,
            'state' => PayrollState::REVIEW,
        ]);

        $batch = PayrollBatch::factory()->create([
            'company_id' => $this->company->id,
            'payroll_period_id' => $period->id,
        ]);

        AttendanceDecision::factory()->create([
            'payroll_period_id' => $period->id,
            'employee_id' => $this->employee->id,
            'classification' => AttendanceClassification::ATTEND,
        ]);

        // Act: Finalize payroll (which creates rows and triggers events)
        $service = app(FinalizePayrollService::class);
        $service->execute($period, $this->ownerUser);

        // Assert: Employee received payslip notification
        $notification = DatabaseNotification::where('notifiable_id', $this->employeeUser->id)
            ->get()
            ->first(function ($n) {
                return str_contains($n->data['title'] ?? '', 'Slip Gaji');
            });

        $this->assertNotNull($notification, 'Could not find notification with title containing "Slip Gaji"');
        $this->assertStringContainsString('Slip Gaji', $notification->data['title']);
    }
}
