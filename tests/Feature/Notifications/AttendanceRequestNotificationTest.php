<?php

namespace Tests\Feature\Notifications;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Domain\Attendance\Services\ApproveAttendanceRequestService;
use App\Domain\Attendance\Services\RejectAttendanceRequestService;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

class AttendanceRequestNotificationTest extends TestCase
{
    use RefreshDatabase, CreatesRoles;

    private Company $company;
    private User $hrUser;
    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRoles();

        // Create company
        $this->company = Company::factory()->create();

        // Create HR user with hr role
        $this->hrUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->hrUser->assignRole('hr');

        // Create employee user
        $this->employeeUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->employeeUser->id,
        ]);
    }

    /** @test */
    public function it_sends_notification_to_hr_when_attendance_request_is_submitted()
    {
        // Act: Submit attendance request via API
        $this->actingAs($this->employeeUser, 'sanctum')
            ->postJson('/api/v1/attendance/requests', [
                'request_type' => AttendanceRequestType::CORRECTION->value,
                'attendance_raw_id' => null,
                'requested_clock_in_at' => now()->format('Y-m-d H:i:s'),
                'requested_clock_out_at' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'reason' => 'Lupa clock in',
            ]);

        // Assert: HR user received notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->hrUser->id,
            'notifiable_type' => User::class,
        ]);

        $notification = DatabaseNotification::where('notifiable_id', $this->hrUser->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Pengajuan Kehadiran Baru', $notification->data['title']);
        $this->assertStringContainsString($this->employee->name, $notification->data['body']);
    }

    /** @test */
    public function it_sends_notification_to_employee_when_request_is_approved()
    {
        // Arrange: Create pending attendance request
        $request = AttendanceRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'status' => AttendanceStatus::PENDING,
            'requested_clock_in_at' => now(),
            'requested_clock_out_at' => now()->addHours(8),
        ]);

        // Act: Approve the request
        $service = app(ApproveAttendanceRequestService::class);
        $service->execute($request, 'Approved', $this->hrUser);

        // Assert: Employee received notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->employeeUser->id,
            'notifiable_type' => User::class,
        ]);

        $notification = DatabaseNotification::where('notifiable_id', $this->employeeUser->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Disetujui', $notification->data['title']);
        $this->assertEquals('success', $notification->data['status']);
    }

    /** @test */
    public function it_sends_notification_to_employee_when_request_is_rejected()
    {
        // Arrange: Create pending attendance request
        $request = AttendanceRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'status' => AttendanceStatus::PENDING,
        ]);

        // Act: Reject the request
        $service = app(RejectAttendanceRequestService::class);
        $service->execute($request, 'Tidak sesuai kebijakan', $this->hrUser);

        // Assert: Employee received notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->employeeUser->id,
            'notifiable_type' => User::class,
        ]);

        $notification = DatabaseNotification::where('notifiable_id', $this->employeeUser->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Ditolak', $notification->data['title']);
        $this->assertEquals('warning', $notification->data['status']);
    }

    /** @test */
    public function it_only_notifies_hr_users_in_same_company()
    {
        // Arrange: Create another company with HR user
        $otherCompany = Company::factory()->create();
        $otherHrUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $otherHrUser->assignRole('hr');

        // Act: Submit attendance request in first company
        $this->actingAs($this->employeeUser, 'sanctum')
            ->postJson('/api/v1/attendance/requests', [
                'request_type' => AttendanceRequestType::CORRECTION->value,
                'requested_clock_in_at' => now()->format('Y-m-d H:i:s'),
                'requested_clock_out_at' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'reason' => 'Lupa clock in',
            ]);

        // Assert: Only HR from same company received notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->hrUser->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $otherHrUser->id,
        ]);
    }

    /** @test */
    public function it_includes_action_button_in_notification()
    {
        // Act: Submit attendance request
        $this->actingAs($this->employeeUser, 'sanctum')
            ->postJson('/api/v1/attendance/requests', [
                'request_type' => AttendanceRequestType::CORRECTION->value,
                'requested_clock_in_at' => now()->format('Y-m-d H:i:s'),
                'requested_clock_out_at' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'reason' => 'Test',
            ]);

        // Assert: Notification has action buttons
        $notification = DatabaseNotification::where('notifiable_id', $this->hrUser->id)->first();
        $this->assertNotNull($notification);
        $this->assertArrayHasKey('actions', $notification->data);
        $this->assertNotEmpty($notification->data['actions']);
    }
}
