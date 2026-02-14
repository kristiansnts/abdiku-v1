<?php

namespace Tests\Feature\Api\V1;

use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

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
        
        // Setup Multi-Company Context
        $this->user->companies()->sync([$this->company->id => ['role' => 'owner']]);
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
