<?php

namespace Tests\Unit\Listeners;

use App\Domain\Attendance\Models\AttendanceRequest;
use App\Events\AttendanceRequestSubmitted;
use App\Listeners\NotifyHrOfAttendanceRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

class NotifyHrOfAttendanceRequestTest extends TestCase
{
    use RefreshDatabase, CreatesRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRoles();
    }

    /** @test */
    public function it_sends_notification_to_all_hr_users_in_company()
    {
        // Arrange: Create company with 2 HR users
        $company = Company::factory()->create();

        $hrUser1 = User::factory()->create(['company_id' => $company->id]);
        $hrUser1->assignRole('hr');

        $hrUser2 = User::factory()->create(['company_id' => $company->id]);
        $hrUser2->assignRole('hr');

        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $request = AttendanceRequest::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
        ]);

        $event = new AttendanceRequestSubmitted($request);
        $listener = new NotifyHrOfAttendanceRequest();

        // Act
        $listener->handle($event);

        // Assert: Both HR users received notification
        $this->assertEquals(2, DatabaseNotification::count());

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $hrUser1->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $hrUser2->id,
        ]);
    }

    /** @test */
    public function it_includes_employee_name_in_notification()
    {
        // Arrange
        $company = Company::factory()->create();
        $hrUser = User::factory()->create(['company_id' => $company->id]);
        $hrUser->assignRole('hr');

        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'name' => 'John Doe',
        ]);

        $request = AttendanceRequest::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
        ]);

        $event = new AttendanceRequestSubmitted($request);
        $listener = new NotifyHrOfAttendanceRequest();

        // Act
        $listener->handle($event);

        // Assert
        $notification = DatabaseNotification::first();
        $this->assertStringContainsString('John Doe', $notification->data['body']);
    }

    /** @test */
    public function it_does_not_notify_non_hr_users()
    {
        // Arrange
        $company = Company::factory()->create();

        $hrUser = User::factory()->create(['company_id' => $company->id]);
        $hrUser->assignRole('hr');

        $regularUser = User::factory()->create(['company_id' => $company->id]);
        // No role assigned

        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $request = AttendanceRequest::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
        ]);

        $event = new AttendanceRequestSubmitted($request);
        $listener = new NotifyHrOfAttendanceRequest();

        // Act
        $listener->handle($event);

        // Assert: Only HR user notified
        $this->assertEquals(1, DatabaseNotification::count());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $hrUser->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $regularUser->id,
        ]);
    }
}
