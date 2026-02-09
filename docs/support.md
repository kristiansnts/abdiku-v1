# Abdiku - Complete Project Guide for AI Support

> **Purpose**: This document provides comprehensive guidance for AI assistants (OpenClaw AI) to understand and support the Abdiku payroll system. Read this document first to understand the project architecture, business logic, and implementation details.

---

## Table of Contents

1. [Project Identity & Purpose](#project-identity--purpose)
2. [Core Philosophy & Constraints](#core-philosophy--constraints)
3. [Technical Stack](#technical-stack)
4. [System Architecture](#system-architecture)
5. [Database Schema Overview](#database-schema-overview)
6. [Business Logic Flow](#business-logic-flow)
7. [User Roles & Permissions](#user-roles--permissions)
8. [Key Features & Modules](#key-features--modules)
9. [Development Guidelines](#development-guidelines)
10. [Deployment Architecture](#deployment-architecture)
11. [Testing Strategy](#testing-strategy)
12. [Common Tasks & Commands](#common-tasks--commands)
13. [Troubleshooting Guide](#troubleshooting-guide)
14. [File Structure Reference](#file-structure-reference)

---

## Project Identity & Purpose

### What is Abdiku?

**Abdiku** is a **payroll decision system** designed for Indonesian SMEs (Small-Medium Enterprises). It is **NOT**:
- An HR dashboard
- A payroll calculator
- An accounting system
- A tax automation tool

### Core Mission

> **"Convert raw attendance and leave facts into explicit, reviewable, and irreversible payroll decisions."**

The system protects business owners by ensuring:
- ✅ No hidden payroll changes
- ✅ No silent recalculations
- ✅ Clear accountability and audit trails
- ✅ Defensible, immutable payroll records

### Primary Problem Being Solved

Most SMEs handle payroll with:
- Excel spreadsheets
- Informal HR judgment
- Last-minute approvals
- Silent recalculations

This creates:
- ❌ Payroll disputes
- ❌ Overpayment/underpayment risks
- ❌ No clear accountability
- ❌ No defensible audit trail

**Abdiku solves payroll risk, not payroll convenience.**

---

## Core Philosophy & Constraints

### Non-Negotiable Principles

1. **Decision Recording System** — Not a calculator
   - System records *decisions*, not just calculations
   - Every decision is traceable and reversible (before finalization)

2. **Immutability After Finalization**
   - Once payroll is finalized, historical records are locked
   - No recalculation of finalized payroll
   - Historical data is preserved for audit and compliance

3. **Owner Authority**
   - Only the company owner can finalize payroll
   - Owners must explicitly approve all payroll outcomes
   - Override authority is limited to owners

4. **Business Logic Isolation**
   - Business logic lives in **Domain Services**, never in UI or models
   - Models are for data storage only
   - Filament resources orchestrate, they don't compute

5. **State-Driven Workflow**
   - Payroll moves through fixed states: DRAFT → REVIEW → FINALIZED → LOCKED
   - **No backward transitions** allowed
   - Each state transition is auditable

### Conceptual Architecture: Fact → Decision → Commitment

```
1. FACTS (Immutable Inputs)
   ├── Raw attendance records
   ├── Approved leave records
   └── Employee data

2. DECISIONS (Computed Interpretations)
   ├── Attendance classification (attend/late/absent)
   ├── Payability determination
   ├── Deduction calculations
   └── Owner-approved overrides

3. COMMITMENT (Frozen Output)
   ├── Payroll batch (immutable)
   ├── Payroll rows (per employee)
   └── Export-ready data for finance
```

---

## Technical Stack

### Core Technologies

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Backend Framework** | Laravel | 12.x | PHP web application framework |
| **Admin Panel** | Filament | 3.3+ | Admin UI and CRUD operations |
| **Authentication** | Laravel Sanctum | 4.3+ | API token authentication |
| **Database** | MySQL / PostgreSQL | 8.0+ / 14+ | Relational data storage |
| **Server Runtime** | Laravel Octane | 2.13+ | High-performance application server |
| **PDF Generation** | DomPDF | 3.1+ | Payslip and report generation |
| **Permissions** | Filament Shield | 3.9+ | Role-based access control |
| **Frontend** | Vite + Tailwind CSS | 4.x | Asset bundling and styling |
| **Monitoring** | Laravel Pulse | 1.5+ | Application performance monitoring |

### Development Dependencies

- **PHP**: ^8.2
- **Composer**: Latest
- **Node.js**: 18+ (for Vite)
- **NPM**: Latest

### Key Laravel Packages

```json
"filament/filament": "^3.3",
"laravel/octane": "^2.13",
"laravel/pulse": "^1.5",
"bezhansalleh/filament-shield": "^3.9",
"barryvdh/laravel-dompdf": "^3.1"
```

---

## System Architecture

### Multi-Tenancy Model

Abdiku uses **company-level multi-tenancy**:
- Each company has isolated data
- Users belong to one company
- All queries are company-scoped
- No cross-company data access

### Domain-Driven Architecture

```
app/
├── Models/               # Data models (storage only)
├── Services/             # Business logic (domain services)
│   ├── Attendance/
│   ├── Payroll/
│   └── Leave/
├── Filament/             # UI orchestration
│   ├── Resources/        # CRUD interfaces
│   ├── Pages/            # Custom pages
│   └── Widgets/          # Dashboard widgets
├── Events/               # Domain events
├── Listeners/            # Event handlers
├── Notifications/        # Notification logic
└── Policies/             # Authorization rules
```

### Service Layer Pattern

All business logic is implemented in **Domain Services**:

```php
// ✅ CORRECT: Business logic in service
class PreparePayrollService {
    public function execute(PayrollPeriod $period): void {
        // Apply rules, compute decisions
        $this->classifyAttendance($period);
        $this->calculatePayability($period);
        $this->detectAnomalies($period);
    }
}

// ❌ WRONG: Business logic in Filament resource
class PayrollResource extends Resource {
    public static function form(Form $form): Form {
        // Only orchestration, NO business logic here
    }
}
```

---

## Database Schema Overview

### Core Tables

#### 1. companies
```sql
id (pk)
name
created_at
```
- Root of multi-tenancy
- All other tables reference company_id

#### 2. users
```sql
id (pk)
company_id (fk)
name
role ENUM('EMPLOYEE','HR','OWNER')
created_at
```
- User authentication and role assignment
- Roles: EMPLOYEE, HR, OWNER

#### 3. payroll_periods
```sql
id (pk)
company_id (fk)
period_start DATE
period_end DATE
state ENUM('DRAFT','REVIEW','FINALIZED','LOCKED')
rule_version VARCHAR
reviewed_at DATETIME NULL
finalized_at DATETIME NULL
finalized_by (fk users.id)
created_at
```
- Represents one payroll cycle
- State machine: DRAFT → REVIEW → FINALIZED → LOCKED
- Only one ACTIVE period per company

#### 4. attendance_raw
```sql
id (pk)
company_id (fk)
employee_id (fk users.id)
date DATE
clock_in DATETIME NULL
clock_out DATETIME NULL
source ENUM('MACHINE','REQUEST','IMPORT')
created_at
```
- **Immutable factual records**
- Records are never updated, only appended
- Source tracking for audit

#### 5. leave_records
```sql
id (pk)
company_id (fk)
employee_id (fk)
date DATE
leave_type ENUM('PAID','UNPAID','SICK_PAID','SICK_UNPAID')
approved_by (fk users.id)
created_at
```
- Approved leave decisions
- Always requires approval authority

#### 6. attendance_decisions
```sql
id (pk)
payroll_period_id (fk)
employee_id (fk)
date DATE
classification ENUM('ATTEND','LATE','ABSENT','PAID_LEAVE','UNPAID_LEAVE','HOLIDAY','PAID_SICK','UNPAID_SICK')
payable BOOLEAN
deduction_type ENUM('NONE','FULL','PERCENTAGE')
deduction_value DECIMAL NULL
rule_version VARCHAR
decided_at DATETIME
```
- System-computed interpretations
- One decision per employee per date per period
- **Recomputed freely until FINALIZED**

#### 7. attendance_overrides
```sql
id (pk)
attendance_decision_id (fk)
old_classification
new_classification
reason TEXT
overridden_by (fk users.id)
overridden_at DATETIME
```
- Explicit owner interventions
- Always records before/after state
- Reason required for audit

#### 8. payroll_batches
```sql
id (pk)
company_id (fk)
payroll_period_id (fk)
total_amount DECIMAL
finalized_by (fk users.id)
finalized_at DATETIME
```
- Frozen payroll commitment
- Created only when state = FINALIZED

#### 9. payroll_rows
```sql
id (pk)
payroll_batch_id (fk)
employee_id (fk)
gross_amount DECIMAL
deduction_amount DECIMAL
net_amount DECIMAL
```
- Per-employee frozen result
- **Immutable after creation**

---

## Business Logic Flow

### 1. Payroll Lifecycle (State Machine)

```
┌──────────────────────────────────────────────────────────────┐
│                    PAYROLL STATE FLOW                         │
└──────────────────────────────────────────────────────────────┘

    DRAFT
      │ (HR prepares, system computes)
      │ - PreparePayrollService
      │ - Can recompute multiple times
      │ - Attendance decisions are mutable
      ▼
    REVIEW
      │ (Owner inspects, overrides if needed)
      │ - ReviewPayrollService
      │ - ApproveOverrideService
      │ - Owner reviews computed decisions
      ▼
    FINALIZED
      │ (Owner commits to payment)
      │ - FinalizePayrollService
      │ - Creates payroll_batch & payroll_rows
      │ - Decisions become immutable
      ▼
    LOCKED
      │ (Historical record, audit-only)
      │ - No modifications allowed
      │ - Used for compliance and disputes
      └─ TERMINAL STATE
```

### 2. Attendance Processing Flow

```
Raw Attendance → Classification → Payability → Deductions → Decisions

1. Read attendance_raw + leave_records
2. Apply company rules (work hours, overtime)
3. Classify each day (ATTEND, LATE, ABSENT, etc.)
4. Determine payability (BOOLEAN)
5. Calculate deductions (NONE, FULL, PERCENTAGE)
6. Write to attendance_decisions
```

### 3. Override Workflow

```
1. HR identifies issue during REVIEW
2. HR requests override from Owner
3. Owner reviews decision context
4. Owner applies override with reason
5. System records old → new state
6. attendance_overrides created
7. attendance_decisions updated
```

### 4. Finalization Workflow

```
1. Owner triggers FinalizePayrollService
2. Validate all approvals complete
3. BEGIN TRANSACTION
   ├── Create payroll_batch
   ├── Create payroll_rows (per employee)
   ├── Set period state = FINALIZED
   ├── Freeze all decisions
4. COMMIT TRANSACTION
5. Generate export for finance
```

---

## User Roles & Permissions

### Authority Hierarchy

```
🏢 OWNER (Company Owner)
   ├── Full system control
   ├── Final payroll approval
   ├── Override authority
   └── Configuration management

👔 HR (HR Manager/Staff)
   ├── Prepare payroll
   ├── Process attendance
   ├── Request overrides
   └── Generate reports

👨‍💼 EMPLOYEE (Regular Staff)
   ├── View own data
   ├── Clock in/out
   ├── Submit leave requests
   └── View own payslips
```

### Permission Matrix

| Action | OWNER | HR | EMPLOYEE |
|--------|-------|----|---------| 
| View all payroll | ✅ | ✅ | ❌ |
| Prepare payroll | ✅ | ✅ | ❌ |
| Finalize payroll | ✅ | ❌ | ❌ |
| Apply overrides | ✅ | ❌ | ❌ |
| View all attendance | ✅ | ✅ | ❌ |
| Modify attendance | ✅ | ✅ | ⚠️ (Own only) |
| Approve leave | ✅ | ✅ | ❌ |
| Submit leave | ✅ | ✅ | ✅ |
| Configure rules | ✅ | ⚠️ (Limited) | ❌ |
| View own payslip | ✅ | ✅ | ✅ |

**Legend:**
- ✅ Full Access
- ⚠️ Limited Access
- ❌ No Access

---

## Key Features & Modules

### 1. Attendance Management
- Clock in/out recording
- Overtime tracking
- Late/absent classification
- Attendance correction requests
- Integration with attendance machines

### 2. Leave Management
- Leave request submission
- Approval workflows (multi-level)
- Leave balance tracking
- Leave types: Paid, Unpaid, Sick (paid/unpaid)
- Leave calendar visualization

### 3. Payroll Processing
- Period-based payroll cycles
- Automatic decision computation
- Manual override capability
- State-driven approval workflow
- Export to finance systems

### 4. Compensation Management
- Employee salary records
- Compensation rules
- Deduction configurations
- Allowance/bonus tracking
- THR (Holiday Allowance) calculation

### 5. Holiday Management
- Company holiday calendar
- Holiday impact on payroll
- Paid holiday handling
- Integration with attendance decisions

### 6. Notification System
- Event-driven notifications
- Role-based notification routing
- Real-time delivery (5s polling)
- Action buttons with navigation
- Multi-tenancy safe

### 7. Reports & Analytics
- Payroll summary reports
- Attendance reports
- Leave reports
- Employee compensation reports
- Export to PDF/Excel

---

## Development Guidelines

### Code Organization Rules

#### ✅ DO:
1. **Put business logic in Services**
   ```php
   // app/Services/Payroll/PreparePayrollService.php
   class PreparePayrollService {
       public function execute(PayrollPeriod $period): void {
           // Business logic here
       }
   }
   ```

2. **Use Models for data storage only**
   ```php
   // app/Models/PayrollPeriod.php
   class PayrollPeriod extends Model {
       protected $fillable = ['period_start', 'period_end', 'state'];
       // No business logic here
   }
   ```

3. **Filament Resources for orchestration only**
   ```php
   // app/Filament/Resources/PayrollResource.php
   public static function form(Form $form): Form {
       return $form->schema([
           // Form fields only, no calculations
       ]);
   }
   ```

4. **Use Events for cross-module communication**
   ```php
   // Trigger event
   event(new PayrollFinalized($batch));
   
   // Handle in listener
   class SendPayrollNotification {
       public function handle(PayrollFinalized $event) {
           // Notification logic
       }
   }
   ```

#### ❌ DON'T:
1. **Don't put business logic in Models**
   ```php
   // ❌ WRONG
   class PayrollPeriod extends Model {
       public function calculatePayroll() {
           // Business logic doesn't belong here
       }
   }
   ```

2. **Don't put business logic in Filament**
   ```php
   // ❌ WRONG
   class PayrollResource extends Resource {
       public static function form(Form $form): Form {
           // Don't calculate here, call a service instead
       }
   }
   ```

3. **Don't bypass the service layer**
   ```php
   // ❌ WRONG
   $period->update(['state' => 'FINALIZED']);
   
   // ✅ CORRECT
   app(FinalizePayrollService::class)->execute($period);
   ```

### Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Models | Singular, PascalCase | `PayrollPeriod` |
| Services | Action + Service | `PreparePayrollService` |
| Events | PastTense + Event | `PayrollFinalized` |
| Listeners | Action + Listener | `SendPayrollNotification` |
| Controllers | Plural + Controller | `PayrollsController` |
| Resources | Plural + Resource | `PayrollResource` |

### Database Query Best Practices

```php
// ✅ CORRECT: Company-scoped queries
$periods = PayrollPeriod::where('company_id', auth()->user()->company_id)
    ->where('state', 'DRAFT')
    ->get();

// ❌ WRONG: Missing company scope
$periods = PayrollPeriod::where('state', 'DRAFT')->get();

// ✅ CORRECT: Use eager loading
$periods = PayrollPeriod::with(['payrollRows.employee'])->get();

// ❌ WRONG: N+1 query problem
$periods = PayrollPeriod::all();
foreach ($periods as $period) {
    $period->payrollRows; // N+1 queries
}
```

---

## Deployment Architecture

### Current Setup

| Service | Domain | Purpose |
|---------|--------|---------|
| Application | `abdiku.dev` | Main Laravel app |
| Dashboard | `dashboard.deskbranch.site` | Coolify management |
| Tunnel | Cloudflare Tunnel | SSL & routing |

### Infrastructure

```
┌─────────────────────────────────────────────┐
│          Cloudflare (CDN + SSL)             │
└─────────────────┬───────────────────────────┘
                  │
         ┌────────▼────────┐
         │ Cloudflare      │
         │ Tunnel          │
         └────────┬────────┘
                  │
         ┌────────▼────────┐
         │ Coolify         │
         │ (Dashboard)     │
         └────────┬────────┘
                  │
    ┌─────────────┼─────────────┐
    │             │             │
┌───▼────┐  ┌────▼────┐  ┌────▼────┐
│ Laravel │  │ MySQL   │  │ Redis   │
│ Octane  │  │ Database│  │ Cache   │
└─────────┘  └─────────┘  └─────────┘
```

### Environment Variables (Production)

```env
APP_NAME=Abdiku
APP_ENV=production
APP_DEBUG=false
APP_URL=https://abdiku.dev
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=abdiku
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Or use Redis
# REDIS_HOST=...
# SESSION_DRIVER=redis
# CACHE_STORE=redis
```

### Deployment Workflow

```bash
# 1. Build assets
npm run build

# 2. Run migrations
php artisan migrate --force

# 3. Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Generate Filament assets
php artisan filament:assets

# 5. Create storage link
php artisan storage:link
```

---

## Testing Strategy

### Test Structure

```
tests/
├── Feature/              # Integration tests
│   ├── Notifications/    # Notification flow tests
│   ├── Payroll/          # Payroll process tests
│   └── Attendance/       # Attendance tests
└── Unit/                 # Isolated unit tests
    ├── Services/         # Service layer tests
    ├── Listeners/        # Event listener tests
    └── Helpers/          # Helper function tests
```

### Running Tests

```bash
# All tests
php artisan test

# Specific test suite
php artisan test --filter=Notification

# Feature tests only
php artisan test tests/Feature

# Unit tests only
php artisan test tests/Unit

# With coverage
php artisan test --coverage
```

### Test Coverage (Current)

- ✅ Notification System: 23 tests
  - 14 Feature tests (complete flows)
  - 9 Unit tests (isolated components)
- ✅ Multi-tenancy tests
- ✅ Edge case handling

### Writing Tests

```php
// ✅ CORRECT: Test services, not UI
public function test_prepare_payroll_computes_decisions()
{
    $period = PayrollPeriod::factory()->create();
    
    $service = app(PreparePayrollService::class);
    $service->execute($period);
    
    $this->assertDatabaseHas('attendance_decisions', [
        'payroll_period_id' => $period->id
    ]);
}

// ❌ WRONG: Don't test implementation details
public function test_attendance_decision_table_exists()
{
    // This tests database, not business logic
}
```

---

## Common Tasks & Commands

### Development

```bash
# Start development server
php artisan serve

# Or with Octane
php artisan octane:start --watch

# Run Vite dev server
npm run dev

# Watch for file changes
php artisan pail
```

### Database

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Refresh database (destructive)
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name
```

### Filament

```bash
# Create new resource
php artisan make:filament-resource ModelName

# Generate Filament assets
php artisan filament:assets

# Create custom page
php artisan make:filament-page PageName

# Create widget
php artisan make:filament-widget WidgetName
```

### Services & Events

```bash
# Create new service (manual)
# Place in app/Services/[Domain]/[Name]Service.php

# Create event
php artisan make:event EventName

# Create listener
php artisan make:listener ListenerName --event=EventName

# List all events
php artisan event:list
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/NotificationTest.php

# Run tests with coverage
php artisan test --coverage

# Create new test
php artisan make:test TestName
php artisan make:test TestName --unit
```

### Cache Management

```bash
# Clear all caches
php artisan optimize:clear

# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear specific cache
php artisan cache:clear
```

### Queue & Jobs

```bash
# Run queue worker
php artisan queue:work

# Process failed jobs
php artisan queue:retry all

# List failed jobs
php artisan queue:failed
```

---

## Troubleshooting Guide

### Common Issues

#### 1. "State transition not allowed"

**Cause**: Trying to transition payroll state incorrectly (e.g., FINALIZED → DRAFT)

**Solution**: 
- State transitions are one-way only
- Check current state before attempting transition
- Use appropriate service for each transition

```php
// ✅ CORRECT
if ($period->state === 'DRAFT') {
    app(ReviewPayrollService::class)->execute($period);
}
```

#### 2. "Company scope violation"

**Cause**: Query not scoped to user's company

**Solution**:
- Always filter by company_id
- Use global scopes for automatic filtering

```php
// ✅ CORRECT
PayrollPeriod::where('company_id', auth()->user()->company_id)->get();
```

#### 3. "Attendance decisions not computing"

**Cause**: Raw attendance data missing or incomplete

**Solution**:
- Verify attendance_raw records exist for period
- Check leave_records for approved leave
- Ensure rule_version is set correctly

```bash
# Debug attendance data
php artisan tinker
>>> AttendanceRaw::where('employee_id', 1)->where('date', '2026-02-01')->first();
```

#### 4. "502 Bad Gateway in production"

**Cause**: 
- Octane not running
- Container stopped in Coolify
- Tunnel disconnected

**Solution**:
```bash
# Check Octane status
php artisan octane:status

# Restart Octane
php artisan octane:restart

# Check Coolify container logs
# via dashboard.deskbranch.site
```

#### 5. "Filament assets not loading"

**Cause**: Assets not compiled or published

**Solution**:
```bash
npm run build
php artisan filament:assets
php artisan optimize:clear
```

#### 6. **Notifications not appearing for HR users**

**Cause**: Filament's `Notification::make()->sendToDatabase()` doesn't persist notifications properly under Laravel Octane due to how Filament handles database notifications in long-running processes.

**Symptoms**:
- Event is dispatched successfully
- Listener executes without errors
- No notifications appear in the database
- No errors in logs

**Solution**: Use Laravel's native notification system instead of Filament's notification builder.

```php
// ❌ WRONG: Filament notifications don't persist under Octane
use Filament\Notifications\Notification;

Notification::make()
    ->title('New Request')
    ->body('Description')
    ->sendToDatabase($user);

// ✅ CORRECT: Use Laravel's native notification system
// 1. Create a notification class
php artisan make:notification AttendanceRequestSubmittedNotification

// 2. Implement the notification
namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AttendanceRequestSubmittedNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Request',
            'body' => 'Description',
            // ... other data
        ];
    }
}

// 3. Send the notification
$user->notify(new AttendanceRequestSubmittedNotification($request));
```

**Why this happens**:
- Filament notifications are designed for the admin panel UI
- They use a different storage mechanism optimized for real-time updates
- Under Octane's long-running process, the database connection state may not persist correctly
- Laravel's native notifications use a more robust database persistence mechanism

**Testing the fix**:
```bash
php artisan tinker
>>> $user = \App\Models\User::find(3);
>>> $user->notify(new \App\Notifications\AttendanceRequestSubmittedNotification($request));
>>> \Illuminate\Notifications\DatabaseNotification::where('notifiable_id', 3)->count();
# Should return 1
```


---

## File Structure Reference

### Key Directories

```
abdiku-v1/
├── app/
│   ├── Events/                    # Domain events
│   ├── Exceptions/                # Custom exceptions
│   ├── Filament/                  # Admin UI
│   │   ├── Resources/             # CRUD interfaces
│   │   ├── Pages/                 # Custom pages
│   │   └── Widgets/               # Dashboard widgets
│   ├── Helpers/                   # Helper functions
│   ├── Http/                      # HTTP layer
│   │   ├── Controllers/           # API controllers
│   │   └── Middleware/            # Request middleware
│   ├── Listeners/                 # Event listeners
│   ├── Models/                    # Eloquent models
│   ├── Notifications/             # Notification classes
│   ├── Policies/                  # Authorization policies
│   ├── Providers/                 # Service providers
│   └── Services/                  # Business logic
│       ├── Attendance/
│       ├── Leave/
│       └── Payroll/
├── config/                        # Configuration files
├── database/
│   ├── migrations/                # Database migrations
│   ├── seeders/                   # Database seeders
│   └── factories/                 # Model factories
├── docs/                          # Documentation
│   ├── HIGH_OVERVIEW.md           # Project overview
│   ├── TRD.md                     # Technical requirements
│   ├── SYSTEM_ROLES_AND_PERMISSIONS.md
│   ├── support.md                 # This file
│   └── api/                       # API documentation
├── public/                        # Public assets
├── resources/
│   ├── views/                     # Blade templates
│   ├── js/                        # JavaScript files
│   └── css/                       # Stylesheets
├── routes/
│   ├── web.php                    # Web routes
│   ├── api.php                    # API routes
│   └── console.php                # Console commands
├── storage/                       # Storage (logs, cache, uploads)
├── tests/
│   ├── Feature/                   # Feature tests
│   └── Unit/                      # Unit tests
├── .env                           # Environment config
├── composer.json                  # PHP dependencies
├── package.json                   # Node dependencies
├── vite.config.js                 # Vite configuration
├── Dockerfile                     # Docker configuration
└── docker-compose.yml             # Docker Compose setup
```

### Important Files

| File | Purpose |
|------|---------|
| `docs/HIGH_OVERVIEW.md` | High-level project overview and philosophy |
| `docs/TRD.md` | Technical requirements and implementation order |
| `docs/SYSTEM_ROLES_AND_PERMISSIONS.md` | Complete permissions matrix |
| `DEPLOYMENT.md` | Deployment guide for Coolify + Cloudflare |
| `NOTIFICATION_IMPLEMENTATION.md` | Notification system documentation |
| `composer.json` | PHP dependencies and autoload configuration |
| `package.json` | Node dependencies and build scripts |
| `.env.example` | Environment variables template |
| `Dockerfile` | Docker container configuration |

---

## AI Assistant Guidelines

### When Supporting Developers

1. **Always Read Core Documents First**
   - HIGH_OVERVIEW.md for philosophy
   - TRD.md for technical requirements
   - SYSTEM_ROLES_AND_PERMISSIONS.md for access control

2. **Respect Non-Negotiable Constraints**
   - Never suggest putting business logic in models or Filament
   - Always enforce company-scoped queries
   - Respect state transition rules
   - Maintain immutability after finalization

3. **Follow Service Layer Pattern**
   - Guide users to create services for business logic
   - Suggest appropriate service names
   - Help organize services by domain

4. **Security First**
   - Always verify company_id scoping
   - Check role permissions before suggesting code
   - Never bypass authorization policies

5. **Test-Driven Approach**
   - Encourage writing tests for new features
   - Suggest appropriate test types (Unit vs Feature)
   - Help with test setup and assertions

### When Debugging Issues

1. **Check State First**
   - Verify payroll period state
   - Check user role and company association
   - Confirm data exists (attendance_raw, leave_records)

2. **Trace Through Services**
   - Start with the service method
   - Check input parameters
   - Verify database queries are company-scoped

3. **Review Event Flow**
   - Check if events are being dispatched
   - Verify listeners are registered
   - Check notification delivery

4. **Common Debug Commands**
   ```bash
   # Check event registration
   php artisan event:list
   
   # Check routes
   php artisan route:list
   
   # Interactive debugging
   php artisan tinker
   
   # View logs
   php artisan pail
   tail -f storage/logs/laravel.log
   ```

### Code Suggestions Format

When providing code suggestions:

```php
// ✅ RECOMMENDED: [Explanation]
// Service-based approach following project architecture
class PreparePayrollService {
    public function execute(PayrollPeriod $period): void {
        // Implementation
    }
}

// ⚠️ ALTERNATIVE: [When to use]
// Direct model approach (only for simple CRUD)
$period->update(['state' => 'REVIEW']);

// ❌ AVOID: [Why not to use]
// Violates service layer pattern
// Business logic in controller
```

---

## Conclusion

This document provides comprehensive guidance for AI assistants supporting the Abdiku payroll system. When in doubt:

1. **Refer to core documents** (HIGH_OVERVIEW.md, TRD.md)
2. **Follow service layer pattern** (business logic in services)
3. **Respect state transitions** (DRAFT → REVIEW → FINALIZED → LOCKED)
4. **Maintain company scoping** (multi-tenancy isolation)
5. **Enforce owner authority** (only owners can finalize)

For specific technical questions, refer to:
- Laravel documentation: https://laravel.com/docs
- Filament documentation: https://filamentphp.com/docs
- Project-specific docs in `/docs` directory

---

**Document Version**: 1.0  
**Last Updated**: 2026-02-08  
**Maintainer**: Abdiku Development Team
