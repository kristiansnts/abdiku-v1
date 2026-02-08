<?php

namespace Tests\Unit\Helpers;

use App\Helpers\NotificationRecipientHelper;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

class NotificationRecipientHelperTest extends TestCase
{
    use RefreshDatabase, CreatesRoles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRoles();
    }

    /** @test */
    public function it_gets_hr_users_for_company()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $hrUser1 = User::factory()->create(['company_id' => $company1->id]);
        $hrUser1->assignRole('hr');

        $hrUser2 = User::factory()->create(['company_id' => $company1->id]);
        $hrUser2->assignRole('hr');

        $hrUser3 = User::factory()->create(['company_id' => $company2->id]);
        $hrUser3->assignRole('hr');

        // Act
        $result = NotificationRecipientHelper::getHrUsers($company1->id);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $hrUser1->id));
        $this->assertTrue($result->contains('id', $hrUser2->id));
        $this->assertFalse($result->contains('id', $hrUser3->id));
    }

    /** @test */
    public function it_gets_owner_users_for_company()
    {
        // Arrange
        $company = Company::factory()->create();

        $ownerUser = User::factory()->create(['company_id' => $company->id]);
        $ownerUser->assignRole('owner');

        $hrUser = User::factory()->create(['company_id' => $company->id]);
        $hrUser->assignRole('hr');

        // Act
        $result = NotificationRecipientHelper::getOwnerUsers($company->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $ownerUser->id));
        $this->assertFalse($result->contains('id', $hrUser->id));
    }

    /** @test */
    public function it_gets_employee_user()
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);

        // Act
        $result = NotificationRecipientHelper::getEmployeeUser($employee);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    /** @test */
    public function it_returns_null_when_employee_has_no_user()
    {
        // Arrange
        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => null,
        ]);

        // Act
        $result = NotificationRecipientHelper::getEmployeeUser($employee);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_gets_all_employee_users_for_company()
    {
        // Arrange
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $employee1 = Employee::factory()->create([
            'company_id' => $company1->id,
            'user_id' => $user1->id,
        ]);

        $user2 = User::factory()->create(['company_id' => $company1->id]);
        $employee2 = Employee::factory()->create([
            'company_id' => $company1->id,
            'user_id' => $user2->id,
        ]);

        $user3 = User::factory()->create(['company_id' => $company2->id]);
        $employee3 = Employee::factory()->create([
            'company_id' => $company2->id,
            'user_id' => $user3->id,
        ]);

        // Act
        $result = NotificationRecipientHelper::getAllEmployeeUsers($company1->id);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $user1->id));
        $this->assertTrue($result->contains('id', $user2->id));
        $this->assertFalse($result->contains('id', $user3->id));
    }

    /** @test */
    public function it_gets_stakeholders_hr_and_owners()
    {
        // Arrange
        $company = Company::factory()->create();

        $hrUser = User::factory()->create(['company_id' => $company->id]);
        $hrUser->assignRole('hr');

        $ownerUser = User::factory()->create(['company_id' => $company->id]);
        $ownerUser->assignRole('owner');

        $employeeUser = User::factory()->create(['company_id' => $company->id]);
        // No role

        // Act
        $result = NotificationRecipientHelper::getStakeholders($company->id);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $hrUser->id));
        $this->assertTrue($result->contains('id', $ownerUser->id));
        $this->assertFalse($result->contains('id', $employeeUser->id));
    }
}
