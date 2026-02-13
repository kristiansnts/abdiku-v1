<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Models\CompanyLocation;
use App\Models\UserDevice;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Domain\Attendance\Models\ShiftPolicy;
use App\Domain\Attendance\Models\WorkPattern;
use App\Domain\Attendance\Models\EmployeeWorkAssignment;
use App\Domain\Attendance\Enums\DayOfWeek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

class MobileAttendanceApiTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreatesRoles;

    private User $user;
    private Employee $employee;
    private Company $company;
    private CompanyLocation $location;
    private UserDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up roles for notification system
        $this->setUpRoles();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ptkp_status' => 'TK/0'
        ]);

        $this->location = CompanyLocation::factory()->create([
            'company_id' => $this->company->id,
            'latitude' => -6.2087,
            'longitude' => 106.8455,
            'geofence_radius_meters' => 100,
            'is_default' => true
        ]);

        $this->device = UserDevice::factory()->create([
            'user_id' => $this->user->id,
            'device_id' => 'test-device-001',
            'device_name' => 'Test Device',
            'is_active' => false
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

        $response->assertStatus(200);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
            'device_id' => 'test-device-002',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_clock_in_within_geofence()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'clock_in_at' => now()->toISOString(),
            'evidence' => [
                'geolocation' => [
                    'lat' => -6.2088,
                    'lng' => 106.8456,
                    'accuracy' => 10.5,
                    'is_mocked' => false
                ],
                'device' => [
                    'device_id' => 'test-device-001',
                    'model' => 'iPhone 14 Pro',
                    'os' => 'iOS 17.2',
                    'app_version' => '1.0.0'
                ]
            ]
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_clock_in_outside_geofence()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'clock_in_at' => now()->toISOString(),
            'evidence' => [
                'geolocation' => [
                    'lat' => -6.3000,
                    'lng' => 106.9000,
                    'accuracy' => 10.5,
                    'is_mocked' => false
                ],
                'device' => [
                    'device_id' => 'test-device-001',
                    'model' => 'iPhone 14 Pro',
                    'os' => 'iOS 17.2',
                    'app_version' => '1.0.0'
                ]
            ]
        ]);

        // Expect 422 because of our new strict geofence
        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_clock_in_with_mocked_location()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'clock_in_at' => now()->toISOString(),
            'evidence' => [
                'geolocation' => [
                    'lat' => -6.2088,
                    'lng' => 106.8456,
                    'accuracy' => 10.5,
                    'is_mocked' => true
                ],
                'device' => [
                    'device_id' => 'test-device-001',
                    'model' => 'iPhone 14 Pro',
                    'os' => 'iOS 17.2',
                    'app_version' => '1.0.0'
                ]
            ]
        ]);

        $response->assertStatus(422);
        $this->assertEquals('MOCK_LOCATION_DETECTED', $response->json('error.code'));
    }
}
