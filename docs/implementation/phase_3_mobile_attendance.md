# Phase 3: Mobile Attendance - Implementation Guide

## Overview

Phase 3 adds mobile attendance features including:
- Attendance evidence (photos, geolocation, device data)
- Attendance requests (late, correction, missing clock-ins)
- Status tracking for attendance records
- Source tracking for attendance decisions

**Goal**: Enable mobile app clock-in/out with proof, request workflows, and full auditability.

---

## Prerequisites

- Phase 1 & Phase 2 completed (recommended)
- Existing attendance system operational
- Database backup completed
- Mobile app development planned or in progress

---

## Implementation Steps

### Step 1: Create Enums

#### 1.1 AttendanceStatus Enum

**File**: `app/Domain/Attendance/Enums/AttendanceStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

enum AttendanceStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case LOCKED = 'LOCKED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::LOCKED => 'Locked (Payroll)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::LOCKED => 'gray',
        };
    }
}
```

#### 1.2 EvidenceType Enum

**File**: `app/Domain/Attendance/Enums/EvidenceType.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain/Attendance\Enums;

enum EvidenceType: string
{
    case GEOLOCATION = 'GEOLOCATION';
    case DEVICE = 'DEVICE';
    case PHOTO = 'PHOTO';

    public function label(): string
    {
        return match ($this) {
            self::GEOLOCATION => 'GPS Location',
            self::DEVICE => 'Device Info',
            self::PHOTO => 'Photo',
        };
    }
}
```

#### 1.3 AttendanceRequestType Enum

**File**: `app/Domain/Attendance/Enums/AttendanceRequestType.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

enum AttendanceRequestType: string
{
    case LATE = 'LATE';
    case CORRECTION = 'CORRECTION';
    case MISSING = 'MISSING';

    public function label(): string
    {
        return match ($this) {
            self::LATE => 'Late Clock-In',
            self::CORRECTION => 'Time Correction',
            self::MISSING => 'Missing Clock',
        };
    }
}
```

---

### Step 2: Create Migrations

#### 2.1 Add Status to attendance_raw

**File**: `database/migrations/2026_01_30_210001_add_status_to_attendance_raw_table.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'LOCKED'])
                ->default('PENDING')
                ->after('source');

            // Add indexes
            $table->index(['employee_id', 'date', 'status']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_raw', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
```

#### 2.2 Create attendance_evidences Table

**File**: `database/migrations/2026_01_30_210002_create_attendance_evidences_table.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_raw_id')->constrained('attendance_raw')->onDelete('cascade');
            $table->enum('type', ['GEOLOCATION', 'DEVICE', 'PHOTO']);
            $table->json('payload');
            $table->datetime('captured_at');
            $table->timestamps();

            // Indexes
            $table->index('attendance_raw_id');
            $table->index(['attendance_raw_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_evidences');
    }
};
```

#### 2.3 Create attendance_requests Table

**File**: `database/migrations/2026_01_30_210003_create_attendance_requests_table.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('attendance_raw_id')->nullable()->constrained('attendance_raw')->onDelete('cascade');
            $table->enum('request_type', ['LATE', 'CORRECTION', 'MISSING']);
            $table->datetime('requested_clock_in_at')->nullable();
            $table->datetime('requested_clock_out_at')->nullable();
            $table->text('reason');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->datetime('requested_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->datetime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index('status');
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
```

#### 2.4 Add Source Tracking to attendance_decisions

**File**: `database/migrations/2026_01_30_210005_add_source_tracking_to_attendance_decisions.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_decisions', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('employee_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->text('reason')->nullable()->after('decided_at');

            // Polymorphic index
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_decisions', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id', 'reason']);
        });
    }
};
```

---

### Step 3: Data Migrations

#### 3.1 Set Default Status on Existing Records

**File**: `database/migrations/2026_01_30_210011_set_default_status_on_existing_attendance_raw.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set all existing records to APPROVED
        DB::table('attendance_raw')
            ->whereNull('status')
            ->update(['status' => 'APPROVED']);
    }

    public function down(): void
    {
        // No rollback needed
    }
};
```

#### 3.2 Populate Source on Existing Decisions

**File**: `database/migrations/2026_01_30_210012_populate_source_on_existing_attendance_decisions.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $decisions = DB::table('attendance_decisions')
            ->select('id', 'employee_id', 'date')
            ->whereNull('source_type')
            ->get();

        foreach ($decisions as $decision) {
            $attendance = DB::table('attendance_raw')
                ->where('employee_id', $decision->employee_id)
                ->where('date', $decision->date)
                ->first();

            if ($attendance) {
                DB::table('attendance_decisions')
                    ->where('id', $decision->id)
                    ->update([
                        'source_type' => 'App\\Domain\\Attendance\\Models\\AttendanceRaw',
                        'source_id' => $attendance->id,
                    ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('attendance_decisions')
            ->update([
                'source_type' => null,
                'source_id' => null,
            ]);
    }
};
```

---

### Step 4: Create Models

#### 4.1 AttendanceEvidence Model

**File**: `app/Domain/Attendance/Models/AttendanceEvidence.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\EvidenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEvidence extends Model
{
    protected $fillable = [
        'attendance_raw_id',
        'type',
        'payload',
        'captured_at',
    ];

    protected $casts = [
        'type' => EvidenceType::class,
        'payload' => 'array',
        'captured_at' => 'datetime',
    ];

    public function attendanceRaw(): BelongsTo
    {
        return $this->belongsTo(AttendanceRaw::class);
    }

    public function getLocationAttribute(): ?array
    {
        if ($this->type === EvidenceType::GEOLOCATION) {
            return [
                'lat' => $this->payload['lat'] ?? null,
                'lng' => $this->payload['lng'] ?? null,
                'accuracy' => $this->payload['accuracy'] ?? null,
            ];
        }

        return null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->type === EvidenceType::PHOTO) {
            return $this->payload['path'] ?? null;
        }

        return null;
    }

    public function getDeviceInfoAttribute(): ?array
    {
        if ($this->type === EvidenceType::DEVICE) {
            return [
                'device_id' => $this->payload['device_id'] ?? null,
                'model' => $this->payload['model'] ?? null,
                'os' => $this->payload['os'] ?? null,
            ];
        }

        return null;
    }
}
```

#### 4.2 AttendanceRequest Model

**File**: `app/Domain/Attendance/Models/AttendanceRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceRequestType;
use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_raw_id',
        'request_type',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'reason',
        'status',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'request_type' => AttendanceRequestType::class,
        'status' => LeaveRequestStatus::class, // Reuse enum
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function attendanceRaw(): BelongsTo
    {
        return $this->belongsTo(AttendanceRaw::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status->value === 'PENDING';
    }

    public function isApproved(): bool
    {
        return $this->status->value === 'APPROVED';
    }

    public function isRejected(): bool
    {
        return $this->status->value === 'REJECTED';
    }
}
```

---

### Step 5: Update Existing Models

#### 5.1 Update AttendanceRaw Model

**File**: `app/Domain/Attendance/Models/AttendanceRaw.php`

Add:

```php
use App\Domain\Attendance\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;

protected $casts = [
    // ... existing casts
    'status' => AttendanceStatus::class,
];

public function evidences(): HasMany
{
    return $this->hasMany(AttendanceEvidence::class);
}

public function requests(): HasMany
{
    return $this->hasMany(AttendanceRequest::class);
}

public function isPending(): bool
{
    return $this->status === AttendanceStatus::PENDING;
}

public function isApproved(): bool
{
    return $this->status === AttendanceStatus::APPROVED;
}

public function isLocked(): bool
{
    return $this->status === AttendanceStatus::LOCKED;
}
```

#### 5.2 Update AttendanceDecision Model

**File**: `app/Domain/Attendance/Models/AttendanceDecision.php`

Add:

```php
use Illuminate\Database\Eloquent\Relations\MorphTo;

protected $fillable = [
    // ... existing fields
    'source_type',
    'source_id',
    'reason',
];

public function source(): MorphTo
{
    return $this->morphTo();
}
```

#### 5.3 Update User Model

**File**: `app/Models/User.php`

Add:

```php
use App\Domain\Attendance\Models\AttendanceRequest;

public function attendanceRequests(): HasMany
{
    return $this->hasMany(AttendanceRequest::class, 'employee_id');
}

public function pendingAttendanceRequests(): HasMany
{
    return $this->attendanceRequests()->where('status', 'PENDING');
}
```

---

### Step 6: Update PreparePayrollService

**File**: `app/Domain/Payroll/Services/PreparePayrollService.php`

Add source tracking when creating attendance decisions:

```php
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Leave\Models\LeaveRequest;

// When creating from attendance
$decision = AttendanceDecision::updateOrCreate(
    [
        'payroll_period_id' => $period->id,
        'employee_id' => $employee->id,
        'date' => $date,
    ],
    [
        // ... existing fields
        'source_type' => AttendanceRaw::class,
        'source_id' => $attendance->id,
        'reason' => $attendance->source === 'REQUEST' ? 'Attendance request approved' : null,
    ]
);

// When creating from leave
$decision = AttendanceDecision::updateOrCreate(
    [
        'payroll_period_id' => $period->id,
        'employee_id' => $employee->id,
        'date' => $date,
    ],
    [
        // ... existing fields
        'source_type' => LeaveRequest::class,
        'source_id' => $leaveRequest->id,
        'reason' => "Leave: {$leaveRequest->leave_type->label()}",
    ]
);
```

---

### Step 7: Create Mobile API Controllers

#### 7.1 ClockInController

**File**: `app/Http/Controllers/Api/V1/Attendance/ClockInController.php`

```php
<?php

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\Models\AttendanceEvidence;
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClockInController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'clock_in_at' => 'required|date',
            'evidence' => 'nullable|array',
            'evidence.geolocation' => 'nullable|array',
            'evidence.geolocation.lat' => 'required_with:evidence.geolocation|numeric',
            'evidence.geolocation.lng' => 'required_with:evidence.geolocation|numeric',
            'evidence.geolocation.accuracy' => 'nullable|numeric',
            'evidence.photo' => 'nullable|image|max:5120', // 5MB
            'evidence.device' => 'nullable|array',
        ]);

        $user = auth()->user();

        $attendance = DB::transaction(function () use ($user, $validated) {
            // Create attendance record
            $attendance = AttendanceRaw::create([
                'company_id' => $user->company_id,
                'employee_id' => $user->id,
                'date' => now()->toDateString(),
                'clock_in' => $validated['clock_in_at'],
                'clock_out' => null,
                'source' => 'REQUEST', // From mobile
                'status' => 'PENDING', // Requires approval
            ]);

            // Store geolocation evidence
            if (isset($validated['evidence']['geolocation'])) {
                AttendanceEvidence::create([
                    'attendance_raw_id' => $attendance->id,
                    'type' => 'GEOLOCATION',
                    'payload' => $validated['evidence']['geolocation'],
                    'captured_at' => now(),
                ]);
            }

            // Store device evidence
            if (isset($validated['evidence']['device'])) {
                AttendanceEvidence::create([
                    'attendance_raw_id' => $attendance->id,
                    'type' => 'DEVICE',
                    'payload' => $validated['evidence']['device'],
                    'captured_at' => now(),
                ]);
            }

            // Store photo evidence
            if (isset($validated['evidence']['photo'])) {
                $path = $request->file('evidence.photo')->store('attendance/photos', 'public');

                AttendanceEvidence::create([
                    'attendance_raw_id' => $attendance->id,
                    'type' => 'PHOTO',
                    'payload' => [
                        'path' => $path,
                        'size' => $request->file('evidence.photo')->getSize(),
                    ],
                    'captured_at' => now(),
                ]);
            }

            return $attendance;
        });

        return response()->json([
            'message' => 'Clock-in successful',
            'attendance' => $attendance->load('evidences'),
        ], 201);
    }
}
```

#### 7.2 AttendanceRequestController

**File**: `app/Http/Controllers/Api/V1/Attendance/AttendanceRequestController.php`

```php
<?php

namespace App\Http\Controllers\Api\V1\Attendance;

use App\Domain\Attendance\Models\AttendanceRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:LATE,CORRECTION,MISSING',
            'attendance_raw_id' => 'nullable|exists:attendance_raw,id',
            'requested_clock_in_at' => 'nullable|date',
            'requested_clock_out_at' => 'nullable|date',
            'reason' => 'required|string|max:1000',
        ]);

        $attendanceRequest = AttendanceRequest::create([
            'employee_id' => auth()->id(),
            'attendance_raw_id' => $validated['attendance_raw_id'] ?? null,
            'request_type' => $validated['request_type'],
            'requested_clock_in_at' => $validated['requested_clock_in_at'] ?? null,
            'requested_clock_out_at' => $validated['requested_clock_out_at'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'PENDING',
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Attendance request submitted',
            'request' => $attendanceRequest,
        ], 201);
    }

    public function index()
    {
        $requests = auth()->user()
            ->attendanceRequests()
            ->with(['attendanceRaw', 'reviewer'])
            ->orderBy('requested_at', 'desc')
            ->get();

        return response()->json($requests);
    }
}
```

---

### Step 8: API Routes

**File**: `routes/api.php`

```php
use App\Http\Controllers\Api\V1\Attendance\ClockInController;
use App\Http\Controllers\Api\V1\Attendance\ClockOutController;
use App\Http\Controllers\Api\V1\Attendance\AttendanceRequestController;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/attendance/clock-in', ClockInController::class);
    Route::post('/attendance/clock-out', ClockOutController::class);

    Route::get('/attendance/requests', [AttendanceRequestController::class, 'index']);
    Route::post('/attendance/requests', [AttendanceRequestController::class, 'store']);

    Route::get('/attendance/history', [AttendanceHistoryController::class, 'index']);
});
```

---

## Running Migrations

```bash
php artisan migrate
```

Expected output:
```
Running migrations.
2026_01_30_210001_add_status_to_attendance_raw_table ............ DONE
2026_01_30_210002_create_attendance_evidences_table .............. DONE
2026_01_30_210003_create_attendance_requests_table ............... DONE
2026_01_30_210005_add_source_tracking_to_attendance_decisions .... DONE
2026_01_30_210011_set_default_status_on_existing_attendance_raw .. DONE
2026_01_30_210012_populate_source_on_existing_attendance_decisions DONE
```

---

## Testing

### 1. Test Status on Existing Records

```php
use App\Domain\Attendance\Models\AttendanceRaw;

$existingRecords = AttendanceRaw::all();
// All should have status 'APPROVED'
dump($existingRecords->pluck('status'));
```

### 2. Test Clock-In with Evidence

```php
// Via API or Tinker
use App\Domain\Attendance\Models\AttendanceRaw;
use App\Domain\Attendance\Models\AttendanceEvidence;

$attendance = AttendanceRaw::create([
    'company_id' => 1,
    'employee_id' => 2,
    'date' => today(),
    'clock_in' => now(),
    'source' => 'REQUEST',
    'status' => 'PENDING',
]);

// Add geolocation evidence
AttendanceEvidence::create([
    'attendance_raw_id' => $attendance->id,
    'type' => 'GEOLOCATION',
    'payload' => [
        'lat' => -6.2088,
        'lng' => 106.8456,
        'accuracy' => 10.5,
    ],
    'captured_at' => now(),
]);

// Verify
dump($attendance->evidences);
dump($attendance->evidences->first()->location);
```

### 3. Test Attendance Request

```php
use App\Domain\Attendance\Models\AttendanceRequest;

$request = AttendanceRequest::create([
    'employee_id' => 2,
    'request_type' => 'LATE',
    'requested_clock_in_at' => now()->subHour(),
    'reason' => 'Traffic jam on highway',
    'status' => 'PENDING',
    'requested_at' => now(),
]);

dump($request->isPending());
```

### 4. Test Source Tracking

```php
use App\Domain\Attendance\Models\AttendanceDecision;

$decision = AttendanceDecision::with('source')->first();

dump($decision->source_type); // Should be polymorphic class name
dump($decision->source); // Should eager load the actual record
```

---

## Validation Checklist

- [ ] All migrations ran successfully
- [ ] Existing attendance records have 'APPROVED' status
- [ ] Attendance evidences can be created with different types
- [ ] Geolocation evidence stores lat/lng correctly
- [ ] Photo evidence stored in storage/app/public
- [ ] Attendance requests can be created from mobile
- [ ] Source tracking populated on existing decisions
- [ ] PreparePayrollService adds source tracking to new decisions
- [ ] API endpoints respond correctly
- [ ] Mobile app can clock-in with evidence

---

## Mobile App Integration

### API Endpoints

```
POST /api/v1/attendance/clock-in
Body: {
  "clock_in_at": "2026-01-30T08:15:00Z",
  "evidence": {
    "geolocation": {"lat": -6.2088, "lng": 106.8456, "accuracy": 10.5},
    "device": {"device_id": "abc123", "model": "Samsung A12", "os": "Android 11"},
    "photo": <file>
  }
}

POST /api/v1/attendance/clock-out
Body: {
  "clock_out_at": "2026-01-30T17:00:00Z"
}

POST /api/v1/attendance/requests
Body: {
  "request_type": "LATE",
  "requested_clock_in_at": "2026-01-30T08:30:00Z",
  "reason": "Traffic jam"
}

GET /api/v1/attendance/requests
GET /api/v1/attendance/history
```

### Authentication

Use Laravel Sanctum for API authentication:

```php
// Mobile login
POST /api/v1/auth/login
Body: {"email": "user@example.com", "password": "password"}
Response: {"token": "..."}

// Use token in headers
Authorization: Bearer {token}
```

---

## Troubleshooting

### Issue: "Existing records have NULL status"

**Solution**: Run data migration again:

```bash
php artisan migrate:refresh --path=database/migrations/2026_01_30_210011_set_default_status_on_existing_attendance_raw.php
```

### Issue: "Source tracking not populated"

**Solution**: Run data migration:

```bash
php artisan migrate:refresh --path=database/migrations/2026_01_30_210012_populate_source_on_existing_attendance_decisions.php
```

### Issue: "Photo upload fails"

**Solution**: Ensure storage link exists:

```bash
php artisan storage:link
```

---

## Next Steps

- **Filament Resources**: Create AttendanceRequestResource, AttendanceEvidenceViewer
- **Approval Workflows**: Create services for approving/rejecting attendance requests
- **Notifications**: Push notifications for approval status
- **Geofencing**: Add validation for clock-in location
- **Reports**: Attendance reports with evidence viewer
