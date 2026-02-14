<?php

namespace Tests\Feature\Notifications;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

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
        $this->ownerUser->companies()->sync([$this->company->id => ['role' => 'owner']]);
        $this->hrUser->companies()->sync([$this->company->id => ['role' => 'hr']]);
        $this->employeeUser->companies()->sync([$this->company->id => ['role' => 'employee']]);
        
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
