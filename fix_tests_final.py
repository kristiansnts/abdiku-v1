import os
import re

def fix_test_file(path, is_notif=False):
    if not os.path.exists(path):
        print(f"File {path} not found")
        return
    
    with open(path, 'r') as f:
        content = f.read()

    # Ensure Tests\Traits\CreatesRoles is imported
    if "use Tests\\Traits\\CreatesRoles;" not in content:
        content = content.replace("use Tests\\TestCase;", "use Tests\\TestCase;\nuse Tests\\Traits\\CreatesRoles;")

    # Ensure the trait is used in the class
    if "use RefreshDatabase, CreatesRoles;" not in content:
        content = re.sub(r'class \w+ extends TestCase\s+\{', 'class \g<0>\n    use RefreshDatabase, CreatesRoles;', content)

    # Define the correct setUp body
    if is_notif:
        setup_logic = """        parent::setUp();
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
        
        $this->withHeader('X-Active-Company-Id', (string) $this->company->id);"""
    else:
        setup_logic = """        parent::setUp();
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
        ]);"""

    # Replace the existing setUp method
    pattern = r'protected function setUp\(\): void\s+\{.*?\}'
    replacement = f'protected function setUp(): void\n    {{\n{setup_logic}\n    }}'
    content = re.sub(pattern, replacement, content, flags=re.DOTALL)

    with open(path, 'w') as f:
        f.write(content)
    print(f"Surgically fixed {path}")

fix_test_file('/home/kristian/.openclaw/workspace/temp_abdiku_v1/tests/Feature/Api/V1/MobileAttendanceApiTest.php')
fix_test_file('/home/kristian/.openclaw/workspace/temp_abdiku_v1/tests/Feature/Notifications/AttendanceRequestNotificationTest.php', is_notif=True)
