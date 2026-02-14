import os

def reconstruct_mobile_test():
    path = '/home/kristian/.openclaw/workspace/temp_abdiku_v1/tests/Feature/Api/V1/MobileAttendanceApiTest.php'
    content = """<?php

namespace Tests\\Feature\\Api\\V1;

use App\\Models\\Company;
use App\\Models\\CompanyLocation;
use App\\Models\\Employee;
use App\\Models\\User;
use App\\Models\\UserDevice;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Laravel\\Sanctum\\Sanctum;
use Tests\\TestCase;
use Tests\\Traits\\CreatesRoles;

class MobileAttendanceApiTest extends TestCase
{
    use RefreshDatabase, CreatesRoles;

    private User $user;
    private Company $company;
    private CompanyLocation $location;
    private UserDevice $device;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRoles();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('owner');
        
        // Link user for Multi-Company Context
        $this->user->companies()->syncWithoutDetaching([$this->company->id => ['role' => 'owner']]);
        $this->withHeader('X-Active-Company-Id', (string) $this->company->id);

        $this->location = CompanyLocation::factory()->create([
            'company_id' => $this->company->id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'geofence_radius_meters' => 100
        ]);

        $this->device = UserDevice::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => 'test-device-123',
            'is_active' => true
        ]);

        Employee::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id
        ]);
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
            'device_id' => 'new-device-001',
            'device_name' => 'New Test Device',
            'device_model' => 'iPhone14,3',
            'device_os' => 'iOS 17.2',
            'app_version' => '1.0.0',
            'force_switch' => false
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => [
                        'id', 'name', 'email', 'role',
                        'employee' => ['id', 'name', 'join_date', 'status']
                    ],
                    'companies' => [
                        '*' => ['id', 'name', 'role']
                    ],
                    'device'
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_logout()
    {
        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(200);
    }
}
"""
    with open(path, 'w') as f: f.write(content)

def reconstruct_notif_test():
    path = '/home/kristian/.openclaw/workspace/temp_abdiku_v1/tests/Feature/Notifications/AttendanceRequestNotificationTest.php'
    content = """<?php

namespace Tests\\Feature\\Notifications;

use App\\Domain\\Attendance\\Enums\\AttendanceRequestType;
use App\\Domain\\Attendance\\Enums\\AttendanceStatus;
use App\\Domain\\Attendance\\Models\\AttendanceRequest;
use App\\Models\\Company;
use App\\Models\\Employee;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;
use Tests\\Traits\\CreatesRoles;

class AttendanceRequestNotificationTest extends TestCase
{
    use RefreshDatabase, CreatesRoles;

    private Company $company;
    private User $ownerUser;
    private User $hrUser;
    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRoles();

        $this->company = Company::factory()->create();
        
        $this->ownerUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->ownerUser->assignRole('owner');
        
        $this->hrUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->hrUser->assignRole('hr');
        
        $this->employeeUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->employeeUser->assignRole('employee');
        
        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
            'company_id' => $this->company->id
        ]);

        // Link users for Multi-Company Context
        $this->ownerUser->companies()->syncWithoutDetaching([$this->company->id => ['role' => 'owner']]);
        $this->hrUser->companies()->syncWithoutDetaching([$this->company->id => ['role' => 'hr']]);
        $this->employeeUser->companies()->syncWithoutDetaching([$this->company->id => ['role' => 'employee']]);
        
        $this->withHeader('X-Active-Company-Id', (string) $this->company->id);
    }

    /** @test */
    public function it_sends_notification_to_hr_when_attendance_request_is_submitted()
    {
        $this->actingAs($this->employeeUser, 'sanctum')
            ->postJson('/api/v1/attendance/requests', [
                'request_type' => AttendanceRequestType::CORRECTION->value,
                'attendance_raw_id' => null,
                'requested_clock_in_at' => now()->format('Y-m-d H:i:s'),
                'reason' => 'Lupa clock in',
            ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->hrUser->id,
            'notifiable_type' => User::class,
        ]);
    }
}
"""
    with open(path, 'w') as f: f.write(content)

reconstruct_mobile_test()
reconstruct_notif_test()
