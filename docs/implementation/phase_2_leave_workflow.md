# Phase 2: Leave Workflow - Implementation Guide

## Overview

Phase 2 adds a complete leave request workflow system, allowing employees to submit leave requests and supervisors/HR to approve or reject them.

**Goal**: Replace direct leave record creation with a formal request → approval workflow.

---

## Prerequisites

- Phase 1 completed (optional, but recommended)
- Existing `leave_records` table (for approved leaves)
- Database backup completed

---

## Implementation Steps

### Step 1: Create Enum

**File**: `app/Domain/Leave/Enums/LeaveRequestStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Leave\Enums;

enum LeaveRequestStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
```

---

### Step 2: Create Migration

**File**: `database/migrations/2026_01_30_210004_create_leave_requests_table.php`

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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->enum('leave_type', ['PAID', 'UNPAID', 'SICK_PAID', 'SICK_UNPAID']);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->text('reason');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->datetime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
```

---

### Step 3: Create Model

**File**: `app/Domain/Leave/Models/LeaveRequest.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Leave\Models;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Enums\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'leave_type' => LeaveType::class,
        'status' => LeaveRequestStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function leaveRecords(): HasMany
    {
        return $this->hasMany(LeaveRecord::class);
    }

    public function isPending(): bool
    {
        return $this->status === LeaveRequestStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === LeaveRequestStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === LeaveRequestStatus::REJECTED;
    }

    public function getDateRangeAttribute(): array
    {
        $period = CarbonPeriod::create($this->start_date, $this->end_date);
        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request) {
            if (!$request->total_days) {
                $request->total_days = $request->calculateBusinessDays();
            }
        });
    }

    public function calculateBusinessDays(): int
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);

        return $start->diffInDaysFiltered(function (Carbon $date) {
            // Exclude weekends (Saturday=6, Sunday=0)
            return !$date->isWeekend();
        }, $end) + 1; // +1 to include end date
    }
}
```

---

### Step 4: Update LeaveRecord Model

**File**: `app/Domain/Leave/Models/LeaveRecord.php`

Add relationship to LeaveRequest:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

public function leaveRequest(): BelongsTo
{
    return $this->belongsTo(LeaveRequest::class);
}
```

Also add `leave_request_id` to migration if tracking is needed (optional):

```php
// Optional: Add to leave_records table
$table->foreignId('leave_request_id')->nullable()->after('id')->constrained()->onDelete('set null');
```

---

### Step 5: Update User Model

**File**: `app/Models/User.php`

Add leave request relationships:

```php
use App\Domain\Leave\Models\LeaveRequest;

public function leaveRequests(): HasMany
{
    return $this->hasMany(LeaveRequest::class, 'employee_id');
}

public function pendingLeaveRequests(): HasMany
{
    return $this->leaveRequests()->where('status', 'PENDING');
}

public function approvedLeaveRequests(): HasMany
{
    return $this->leaveRequests()->where('status', 'APPROVED');
}
```

---

### Step 6: Create ApproveLeaveRequestService

**File**: `app/Domain/Leave/Services/ApproveLeaveRequestService.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Leave\Services;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Models\LeaveRecord;
use App\Domain\Leave\Models\LeaveRequest;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ApproveLeaveRequestService
{
    public function execute(LeaveRequest $request, User $approver): void
    {
        // Validate request is pending
        if (!$request->isPending()) {
            throw new \RuntimeException('Only pending leave requests can be approved');
        }

        // Validate approver has permission (HR or OWNER)
        if (!in_array($approver->role, ['HR', 'OWNER'])) {
            throw new \UnauthorizedException('User does not have permission to approve leave requests');
        }

        DB::transaction(function () use ($request, $approver) {
            // Update request status
            $request->update([
                'status' => LeaveRequestStatus::APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Create leave records for each day
            $period = CarbonPeriod::create($request->start_date, $request->end_date);

            foreach ($period as $date) {
                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                LeaveRecord::create([
                    'company_id' => $request->employee->company_id,
                    'employee_id' => $request->employee_id,
                    'date' => $date->format('Y-m-d'),
                    'leave_type' => $request->leave_type,
                    'approved_by' => $approver->id,
                    // Optionally track source
                    // 'leave_request_id' => $request->id,
                ]);
            }
        });

        // TODO: Send notification to employee
        // Notification::send($request->employee, new LeaveRequestApprovedNotification($request));
    }
}
```

---

### Step 7: Create RejectLeaveRequestService

**File**: `app/Domain/Leave/Services/RejectLeaveRequestService.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Leave\Services;

use App\Domain\Leave\Enums\LeaveRequestStatus;
use App\Domain\Leave\Models\LeaveRequest;
use App\Models\User;

class RejectLeaveRequestService
{
    public function execute(LeaveRequest $request, User $rejector, string $reason): void
    {
        // Validate request is pending
        if (!$request->isPending()) {
            throw new \RuntimeException('Only pending leave requests can be rejected');
        }

        // Validate rejector has permission (HR or OWNER)
        if (!in_array($rejector->role, ['HR', 'OWNER'])) {
            throw new \UnauthorizedException('User does not have permission to reject leave requests');
        }

        $request->update([
            'status' => LeaveRequestStatus::REJECTED,
            'approved_by' => $rejector->id, // Store who rejected
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // TODO: Send notification to employee
        // Notification::send($request->employee, new LeaveRequestRejectedNotification($request));
    }
}
```

---

### Step 8: Create Exception Classes (Optional)

**File**: `app/Domain/Leave/Exceptions/UnauthorizedLeaveActionException.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Leave\Exceptions;

class UnauthorizedLeaveActionException extends \Exception
{
    public function __construct(string $message = 'Unauthorized to perform this leave action')
    {
        parent::__construct($message);
    }
}
```

Update services to use this exception:

```php
use App\Domain\Leave\Exceptions\UnauthorizedLeaveActionException;

if (!in_array($approver->role, ['HR', 'OWNER'])) {
    throw new UnauthorizedLeaveActionException('User does not have permission to approve leave requests');
}
```

---

## Running Migration

```bash
php artisan migrate
```

Expected output:
```
Running migrations.
2026_01_30_210004_create_leave_requests_table ................... DONE
```

---

## Post-Implementation Testing

### 1. Create Leave Request

```php
use App\Domain\Leave\Models\LeaveRequest;

$request = LeaveRequest::create([
    'employee_id' => 2, // Employee user
    'leave_type' => 'PAID',
    'start_date' => '2026-02-10',
    'end_date' => '2026-02-14',
    'total_days' => 5,
    'reason' => 'Family vacation',
    'status' => 'PENDING',
]);

dump($request->isPending()); // true
dump($request->total_days); // Auto-calculated if not provided
```

### 2. Approve Leave Request

```php
use App\Domain\Leave\Services\ApproveLeaveRequestService;

$approver = User::find(1); // HR or OWNER
$service = app(ApproveLeaveRequestService::class);

$service->execute($request, $approver);

// Verify
$request->refresh();
dump($request->isApproved()); // true
dump($request->approved_by); // 1
dump($request->approved_at); // Current timestamp

// Check leave records created
$leaveRecords = \App\Domain\Leave\Models\LeaveRecord::where('employee_id', 2)
    ->whereBetween('date', ['2026-02-10', '2026-02-14'])
    ->get();

dump($leaveRecords->count()); // 5 (weekdays only)
```

### 3. Reject Leave Request

```php
use App\Domain\Leave\Services\RejectLeaveRequestService;

$rejector = User::find(1); // HR or OWNER
$service = app(RejectLeaveRequestService::class);

$service->execute($request, $rejector, 'Insufficient leave balance');

// Verify
$request->refresh();
dump($request->isRejected()); // true
dump($request->rejection_reason); // 'Insufficient leave balance'
```

### 4. Test Business Days Calculation

```php
$request = LeaveRequest::make([
    'start_date' => '2026-02-10', // Monday
    'end_date' => '2026-02-16',   // Sunday
]);

dump($request->calculateBusinessDays()); // 5 (Mon-Fri, excludes Sat-Sun)
```

---

## Workflow Integration

### Employee Submits Request (API/Web)

```php
// In controller
use App\Domain\Leave\Models\LeaveRequest;

public function store(Request $request)
{
    $validated = $request->validate([
        'leave_type' => 'required|in:PAID,UNPAID,SICK_PAID,SICK_UNPAID',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'required|string|max:1000',
    ]);

    $leaveRequest = LeaveRequest::create([
        'employee_id' => auth()->id(),
        'leave_type' => $validated['leave_type'],
        'start_date' => $validated['start_date'],
        'end_date' => $validated['end_date'],
        'reason' => $validated['reason'],
        'status' => 'PENDING',
    ]);

    // TODO: Notify HR/supervisor

    return response()->json($leaveRequest, 201);
}
```

### HR Reviews Request (Filament/Web)

```php
// In Filament resource action
use App\Domain\Leave\Services\ApproveLeaveRequestService;
use App\Domain\Leave\Services\RejectLeaveRequestService;

// Approve action
Action::make('approve')
    ->action(function (LeaveRequest $record) {
        $service = app(ApproveLeaveRequestService::class);
        $service->execute($record, auth()->user());

        Notification::make()
            ->success()
            ->title('Leave request approved')
            ->send();
    })
    ->requiresConfirmation()
    ->visible(fn (LeaveRequest $record) => $record->isPending());

// Reject action
Action::make('reject')
    ->form([
        Textarea::make('rejection_reason')
            ->required()
            ->label('Reason for Rejection'),
    ])
    ->action(function (LeaveRequest $record, array $data) {
        $service = app(RejectLeaveRequestService::class);
        $service->execute($record, auth()->user(), $data['rejection_reason']);

        Notification::make()
            ->success()
            ->title('Leave request rejected')
            ->send();
    })
    ->requiresConfirmation()
    ->visible(fn (LeaveRequest $record) => $record->isPending());
```

---

## Validation Checklist

- [ ] Migration ran successfully
- [ ] LeaveRequest model created with proper relationships
- [ ] Leave request can be created with PENDING status
- [ ] ApproveLeaveRequestService creates leave records for each day
- [ ] Weekends are excluded from leave records
- [ ] RejectLeaveRequestService updates status and stores reason
- [ ] Only HR/OWNER can approve/reject requests
- [ ] Approved/rejected requests cannot be modified
- [ ] Employee can see their leave request history
- [ ] HR can see all pending leave requests

---

## Filament Resource Example

**File**: `app/Filament/Resources/Leave/LeaveRequestResource.php`

```php
<?php

namespace App\Filament\Resources\Leave;

use App\Domain\Leave\Models\LeaveRequest;
use App\Domain\Leave\Services\ApproveLeaveRequestService;
use App\Domain\Leave\Services\RejectLeaveRequestService;
use App\Filament\Resources\Leave\LeaveRequestResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Leave Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->required()
                    ->disabled(fn ($context) => $context === 'edit'),

                Forms\Components\Select::make('leave_type')
                    ->options([
                        'PAID' => 'Paid Leave',
                        'UNPAID' => 'Unpaid Leave',
                        'SICK_PAID' => 'Sick Leave (Paid)',
                        'SICK_UNPAID' => 'Sick Leave (Unpaid)',
                    ])
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->required(),

                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->maxLength(1000),

                Forms\Components\Select::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ])
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('leave_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Days'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->isPending())
                    ->action(function (LeaveRequest $record) {
                        $service = app(ApproveLeaveRequestService::class);
                        $service->execute($record, auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Leave request approved')
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->required()
                            ->label('Reason for Rejection'),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (LeaveRequest $record) => $record->isPending())
                    ->action(function (LeaveRequest $record, array $data) {
                        $service = app(RejectLeaveRequestService::class);
                        $service->execute($record, auth()->user(), $data['rejection_reason']);

                        Notification::make()
                            ->success()
                            ->title('Leave request rejected')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
        ];
    }
}
```

---

## Troubleshooting

### Issue: "Only pending leave requests can be approved"

**Solution**: Check request status before approval:

```php
$request->status; // Should be 'PENDING'
```

### Issue: "Weekends included in leave records"

**Solution**: The service already excludes weekends. Verify with:

```php
$records = LeaveRecord::whereBetween('date', ['2026-02-10', '2026-02-16'])->get();
// Should only include weekdays
```

### Issue: "Unauthorized exception"

**Solution**: Ensure approver has correct role:

```php
$approver->role; // Should be 'HR' or 'OWNER'
```

---

## Next Steps

- **Phase 1**: Implement employee compensation and payroll calculations
- **Phase 3**: Add mobile attendance features
- **Notifications**: Implement email/SMS notifications for leave approvals
- **Leave Balance**: Track leave balances and entitlements
- **Calendar Integration**: Show approved leaves on company calendar
