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
use App\Notifications\AttendanceRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
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

    /** @test */
    public function notification_has_filament_compatible_format()
    {
        // Act: Submit attendance request
        $this->actingAs($this->employeeUser, 'sanctum')
            ->postJson('/api/v1/attendance/requests', [
                'request_type' => AttendanceRequestType::CORRECTION->value,
                'requested_clock_in_at' => now()->format('Y-m-d H:i:s'),
                'requested_clock_out_at' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'reason' => 'Test Filament Format',
            ]);

        // Assert: Notification has Filament-compatible format
        $notification = DatabaseNotification::where('notifiable_id', $this->hrUser->id)->first();
        $this->assertNotNull($notification);

        // Check required Filament fields
        $this->assertEquals('filament', $notification->data['format']);
        $this->assertArrayHasKey('title', $notification->data);
        $this->assertArrayHasKey('body', $notification->data);
        $this->assertArrayHasKey('icon', $notification->data);
        $this->assertArrayHasKey('iconColor', $notification->data);
        $this->assertArrayHasKey('duration', $notification->data);
        $this->assertArrayHasKey('view', $notification->data);
        $this->assertArrayHasKey('viewData', $notification->data);
        $this->assertArrayHasKey('actions', $notification->data);

        // Verify values
        $this->assertEquals('persistent', $notification->data['duration']);
        $this->assertEquals('filament-notifications::notification', $notification->data['view']);
        $this->assertEquals('heroicon-o-document-text', $notification->data['icon']);
        $this->assertEquals('info', $notification->data['iconColor']);
    }

    /** @test */
    public function notification_actions_have_correct_structure()
    {
        // Act: Submit attendance request
        $this->actingAs($this->employeeUser, 'sanctum')
            ->postJson('/api/v1/attendance/requests', [
                'request_type' => AttendanceRequestType::CORRECTION->value,
                'requested_clock_in_at' => now()->format('Y-m-d H:i:s'),
                'requested_clock_out_at' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'reason' => 'Test Actions',
            ]);

        // Assert: Actions have correct Filament structure
        $notification = DatabaseNotification::where('notifiable_id', $this->hrUser->id)->first();
        $this->assertNotNull($notification);
        $this->assertIsArray($notification->data['actions']);
        $this->assertNotEmpty($notification->data['actions']);

        $action = $notification->data['actions'][0];

        // Check required action fields
        $this->assertArrayHasKey('name', $action);
        $this->assertArrayHasKey('label', $action);
        $this->assertArrayHasKey('url', $action);
        $this->assertArrayHasKey('view', $action);
        $this->assertArrayHasKey('shouldMarkAsRead', $action);
        $this->assertArrayHasKey('color', $action);

        // Verify action values
        $this->assertEquals('view', $action['name']);
        $this->assertEquals('Lihat', $action['label']);
        $this->assertTrue($action['shouldMarkAsRead']);
        $this->assertEquals('filament-notifications::actions.button-action', $action['view']);
    }

    /** @test */
    public function notification_persists_correctly_under_octane()
    {
        // This test verifies that notifications are persisted to database
        // even when running under Laravel Octane

        // Act: Submit attendance request
        $response = $this->actingAs($this->employeeUser, 'sanctum')
            ->postJson('/api/v1/attendance/requests', [
                'request_type' => AttendanceRequestType::CORRECTION->value,
                'requested_clock_in_at' => now()->format('Y-m-d H:i:s'),
                'requested_clock_out_at' => now()->addHours(8)->format('Y-m-d H:i:s'),
                'reason' => 'Octane Test',
            ]);

        $response->assertStatus(201);

        // Assert: Notification was persisted to database
        $notificationCount = DatabaseNotification::where('notifiable_id', $this->hrUser->id)->count();
        $this->assertEquals(1, $notificationCount);

        // Verify notification type is correct
        $notification = DatabaseNotification::where('notifiable_id', $this->hrUser->id)->first();
        $this->assertEquals(AttendanceRequestSubmittedNotification::class, $notification->type);
    }

    /** @test */
    public function notification_can_be_sent_directly_via_notification_class()
    {
        // Arrange: Create attendance request
        $request = AttendanceRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
        ]);

        // Act: Send notification directly
        $this->hrUser->notify(new AttendanceRequestSubmittedNotification($request));

        // Assert: Notification was created
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->hrUser->id,
            'notifiable_type' => User::class,
            'type' => AttendanceRequestSubmittedNotification::class,
        ]);

        $notification = DatabaseNotification::where('notifiable_id', $this->hrUser->id)->first();
        $this->assertNotNull($notification);
        $this->assertEquals('filament', $notification->data['format']);
    }
}
