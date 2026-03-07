<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Leave\Models\LeaveBalance;
use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Leave\Models\LeaveType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\CreatesRoles;

class LeaveRequestApiTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreatesRoles;

    private User $user;
    private Employee $employee;
    private Company $company;
    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->setUpRoles();

        $this->company  = Company::factory()->create();
        $this->user     = User::factory()->create(['company_id' => $this->company->id]);
        $this->employee = Employee::factory()->create([
            'user_id'    => $this->user->id,
            'company_id' => $this->company->id,
            'ptkp_status' => 'TK/0',
        ]);

        // CompanyObserver creates default leave types when the company is created.
        // Fetch the 'sick' type — deduct_from_balance:false so no balance record needed.
        $this->leaveType = LeaveType::where('company_id', $this->company->id)
            ->where('code', 'sick')
            ->firstOrFail();
    }

    // =========================================================================
    // Submit without attachment
    // =========================================================================

    /** @test */
    public function it_can_submit_leave_request_without_attachment()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDays(2)->toDateString(),
            'reason'        => 'Demam tinggi',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'leave_type_id',
                    'start_date',
                    'end_date',
                    'total_days',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('leave_requests', [
            'employee_id'     => $this->employee->id,
            'leave_type_id'   => $this->leaveType->id,
            'attachment_path' => null,
        ]);
    }

    // =========================================================================
    // Submit with image attachment
    // =========================================================================

    /** @test */
    public function it_can_submit_leave_request_with_jpeg_attachment()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->image('surat_dokter.jpg', 800, 600);

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDays(2)->toDateString(),
            'reason'        => 'Sakit flu',
            'attachment'    => $file,
        ]);

        $response->assertStatus(201);

        $path = $response->json('data.attachment_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_can_submit_leave_request_with_png_attachment()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->image('surat_dokter.png', 600, 400);

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
            'attachment'    => $file,
        ]);

        $response->assertStatus(201);

        $path = $response->json('data.attachment_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_can_submit_leave_request_with_pdf_attachment()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('surat_keterangan.pdf', 512, 'application/pdf');

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDays(3)->toDateString(),
            'reason'        => 'Opname rumah sakit',
            'attachment'    => $file,
        ]);

        $response->assertStatus(201);

        $path = $response->json('data.attachment_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    // =========================================================================
    // Attachment stored in correct directory
    // =========================================================================

    /** @test */
    public function it_stores_attachment_under_leave_attachments_directory()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
            'attachment'    => $file,
        ]);

        $response->assertStatus(201);

        $path = $response->json('data.attachment_path');
        $this->assertStringStartsWith('leave-attachments/', $path);
    }

    /** @test */
    public function it_persists_attachment_path_in_database()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->image('foto_sakit.jpg');

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
            'attachment'    => $file,
        ]);

        $response->assertStatus(201);

        $id = $response->json('data.id');
        $record = LeaveRequest::find($id);
        $this->assertNotNull($record->attachment_path);
        Storage::disk('public')->assertExists($record->attachment_path);
    }

    // =========================================================================
    // Attachment validation errors
    // =========================================================================

    /** @test */
    public function it_rejects_attachment_exceeding_5mb_limit()
    {
        Sanctum::actingAs($this->user);

        // 6 MB file (6144 KB)
        $file = UploadedFile::fake()->create('large_file.pdf', 6144, 'application/pdf');

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
            'attachment'    => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);
    }

    /** @test */
    public function it_rejects_unsupported_attachment_type()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
            'attachment'    => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);
    }

    /** @test */
    public function it_rejects_text_file_as_attachment()
    {
        Sanctum::actingAs($this->user);

        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
            'attachment'    => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);
    }

    // =========================================================================
    // Field validation
    // =========================================================================

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDay()->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/leave/requests', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['leave_type_id', 'start_date', 'end_date']);
    }

    /** @test */
    public function it_rejects_past_start_date()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->subDay()->toDateString(),
            'end_date'      => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /** @test */
    public function it_rejects_end_date_before_start_date()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/leave/requests', [
            'leave_type_id' => $this->leaveType->id,
            'start_date'    => now()->addDays(3)->toDateString(),
            'end_date'      => now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    // =========================================================================
    // List and balance endpoints
    // =========================================================================

    /** @test */
    public function it_can_get_leave_request_history()
    {
        Sanctum::actingAs($this->user);

        LeaveRequest::create([
            'employee_id'  => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date'   => now()->addDay()->toDateString(),
            'end_date'     => now()->addDays(2)->toDateString(),
            'total_days'   => 2,
            'reason'       => 'Test',
            'status'       => 'PENDING',
        ]);

        $response = $this->getJson('/api/v1/leave/requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'leave_type_id', 'start_date', 'end_date', 'status'],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function it_can_get_leave_balances()
    {
        Sanctum::actingAs($this->user);

        LeaveBalance::create([
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year'          => now()->year,
            'balance'       => 12,
        ]);

        $response = $this->getJson('/api/v1/leave/balances');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'year',
                'balances' => [
                    '*' => ['id', 'leave_type_id', 'year', 'balance'],
                ],
            ]);

        $this->assertEquals(12, $response->json('balances.0.balance'));
    }
}
