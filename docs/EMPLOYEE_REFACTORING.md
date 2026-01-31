# Employee Refactoring - Separation from Users

## Overview

Refactored the system to separate **Users** (authentication) from **Employees** (business entities). This provides better separation of concerns and aligns with the ERD requirements.

---

## Changes Made

### 1. New Tables & Models

#### Created `employees` Table
**Migration**: `2026_01_30_210000_create_employees_table.php` (runs before all Phase 1 & 2 migrations)

**Fields**:
- `id` - Primary key
- `company_id` - FK to companies
- `user_id` - FK to users (nullable, for authentication link)
- `name` - Employee name
- `join_date` - Date employee joined
- `resign_date` - Date employee resigned (nullable)
- `status` - ACTIVE, INACTIVE, RESIGNED

**Model**: `app/Models/Employee.php`

**Purpose**: Represents business employees separate from authentication users.

---

### 2. Updated Foreign Keys

All employee-related tables now reference `employees` instead of `users`:

| Table | Old FK | New FK |
|-------|--------|--------|
| `employee_compensations` | `users.id` | `employees.id` |
| `payroll_additions` | `users.id` | `employees.id` |
| `payroll_row` | `users.id` | `employees.id` |
| `leave_requests` | `users.id` | `employees.id` |
| `attendance_raw` | `users.id` | `employees.id` |
| `leave_records` | `users.id` | `employees.id` |

---

### 3. Updated Models

**Updated to use `Employee` instead of `User`**:
- `EmployeeCompensation` → `employee(): BelongsTo(Employee::class)`
- `PayrollAddition` → `employee(): BelongsTo(Employee::class)`
- `PayrollRow` → `employee(): BelongsTo(Employee::class)`
- `LeaveRequest` → `employee(): BelongsTo(Employee::class)`

**User Model** - Simplified to:
```php
public function employee(): HasOne
{
    return $this->hasOne(Employee::class);
}
```

**Company Model** - Added:
```php
public function employees()
{
    return $this->hasMany(Employee::class);
}
```

---

### 4. Updated Services

**CalculatePayrollService**:
- Changed from `User` to `Employee` parameters
- Now fetches: `$period->company->employees()->where('status', 'ACTIVE')->get()`

**ApproveLeaveRequestService**:
- Works with `Employee` model
- Relationships updated accordingly

---

## Migration Order

The migrations will run in this order:

```bash
1. 2026_01_30_210000_create_employees_table         # NEW - Base employee table
2. 2026_01_30_210004_create_leave_requests_table    # Updated FK
3. 2026_01_30_210006_create_employee_compensations  # Updated FK
4. 2026_01_30_210007_create_payroll_deduction_rules
5. 2026_01_30_210008_create_payroll_additions       # Updated FK
6. 2026_01_30_210009_create_payroll_row_deductions
7. 2026_01_30_210010_create_payroll_row_additions
```

---

## Data Migration Required

After running migrations, you need to:

### 1. Create Employee Records from Existing Users

```php
use App\Models\Employee;
use App\Models\User;

// For each existing user, create an employee
User::whereIn('role', ['EMPLOYEE', 'HR'])->each(function ($user) {
    Employee::create([
        'company_id' => $user->company_id,
        'user_id' => $user->id,
        'name' => $user->name,
        'join_date' => now(), // Or fetch from another source
        'status' => 'ACTIVE',
    ]);
});
```

### 2. Link Existing Records to New Employees

If you have existing data in `attendance_raw`, `leave_records`, etc., you'll need to update those records to reference the new `employees` table instead of `users`.

**Important**: Only run these migrations on a **fresh database** or ensure you have a data migration plan for existing data.

---

## Running Migrations

```bash
# Fresh install (recommended for new setup)
php artisan migrate:fresh

# Or on existing database (WARNING: may cause FK constraint issues)
php artisan migrate
```

---

## Benefits of This Refactoring

✅ **Clear Separation**: Users = Authentication, Employees = Business Logic
✅ **Better Data Model**: Employees can exist without user accounts
✅ **Flexible**: Multiple users can be linked to one employee (future feature)
✅ **ERD Compliant**: Matches the intended design from documentation
✅ **Status Tracking**: Track employee lifecycle (ACTIVE, INACTIVE, RESIGNED)
✅ **Join/Resign Dates**: Proper employee lifecycle management

---

## Relationships

```
User (1) ←→ (0..1) Employee
         ↓
    Company (1) ←→ (*) Employee
                     ↓
                 EmployeeCompensation
                 PayrollAdditions
                 LeaveRequests
                 AttendanceRecords
                 PayrollRows
```

---

## Next Steps

1. **Run migrations**: `php artisan migrate:fresh`
2. **Create employee records**: Seed or manually create employees
3. **Link users to employees**: Assign `user_id` where authentication is needed
4. **Test payroll flow**: Verify calculation works with new Employee model
5. **Update Filament resources**: Create/update EmployeeResource

---

## Filament Integration

You'll need to create an `EmployeeResource` in Filament to manage employees:

```bash
php artisan make:filament-resource Employee --generate
```

This will create:
- EmployeeResource
- List page
- Create page
- Edit page

Then add relationships for compensations, leave requests, attendance, etc.
