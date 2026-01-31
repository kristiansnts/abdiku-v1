# Phase 1: Foundation - Implementation Guide

## Overview

Phase 1 creates the foundation for detailed payroll calculations with employee compensation tracking, deduction rules (BPJS, tax), and transparent payroll breakdowns.

**Goal**: Enable HR to process payroll with complete visibility into salary components, deductions, and additions.

---

## Prerequisites

- Laravel 12.x application with existing payroll system
- Database backup completed
- Staging environment for testing

---

## Implementation Steps

### Step 1: Create Enums

#### 1.1 DeductionBasisType Enum

**File**: `app/Domain/Payroll/Enums/DeductionBasisType.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum DeductionBasisType: string
{
    case BASE_SALARY = 'BASE_SALARY';
    case CAPPED_SALARY = 'CAPPED_SALARY';
    case GROSS_SALARY = 'GROSS_SALARY';

    public function label(): string
    {
        return match ($this) {
            self::BASE_SALARY => 'Base Salary Only',
            self::CAPPED_SALARY => 'Capped Salary',
            self::GROSS_SALARY => 'Gross Salary (Base + Allowances)',
        };
    }
}
```

#### 1.2 PayrollAdditionCode Enum

**File**: `app/Domain/Payroll/Enums/PayrollAdditionCode.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Enums;

enum PayrollAdditionCode: string
{
    case THR = 'THR';
    case BONUS = 'BONUS';
    case INCENTIVE = 'INCENTIVE';
    case OVERTIME = 'OVERTIME';
    case ADJUSTMENT = 'ADJUSTMENT';

    public function label(): string
    {
        return match ($this) {
            self::THR => 'THR (Tunjangan Hari Raya)',
            self::BONUS => 'Bonus',
            self::INCENTIVE => 'Incentive',
            self::OVERTIME => 'Overtime Pay',
            self::ADJUSTMENT => 'Manual Adjustment',
        };
    }
}
```

---

### Step 2: Create Migrations

#### 2.1 Employee Compensations Table

**File**: `database/migrations/2026_01_30_210006_create_employee_compensations_table.php`

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
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->decimal('base_salary', 12, 2);
            $table->json('allowances')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'effective_from', 'effective_to']);
            $table->index('effective_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};
```

#### 2.2 Payroll Deduction Rules Table

**File**: `database/migrations/2026_01_30_210007_create_payroll_deduction_rules_table.php`

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
        Schema::create('payroll_deduction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('code', 50);
            $table->string('name');
            $table->enum('basis_type', ['BASE_SALARY', 'CAPPED_SALARY', 'GROSS_SALARY']);
            $table->decimal('employee_rate', 5, 2)->nullable();
            $table->decimal('employer_rate', 5, 2)->nullable();
            $table->decimal('salary_cap', 12, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'code', 'effective_from']);
            $table->index(['effective_from', 'effective_to']);
            $table->unique(['company_id', 'code', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_deduction_rules');
    }
};
```

#### 2.3 Payroll Additions Table

**File**: `database/migrations/2026_01_30_210008_create_payroll_additions_table.php`

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
        Schema::create('payroll_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payroll_period_id')->constrained()->onDelete('cascade');
            $table->string('code', 50);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Indexes
            $table->index(['payroll_period_id', 'employee_id']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_additions');
    }
};
```

#### 2.4 Payroll Row Deductions Table

**File**: `database/migrations/2026_01_30_210009_create_payroll_row_deductions_table.php`

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
        Schema::create('payroll_row_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_row_id')->constrained('payroll_rows')->onDelete('cascade');
            $table->string('deduction_code', 50);
            $table->decimal('employee_amount', 12, 2);
            $table->decimal('employer_amount', 12, 2)->default(0);
            $table->json('rule_snapshot');
            $table->timestamps();

            // Indexes
            $table->index('payroll_row_id');
            $table->index('deduction_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_row_deductions');
    }
};
```

#### 2.5 Payroll Row Additions Table

**File**: `database/migrations/2026_01_30_210010_create_payroll_row_additions_table.php`

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
        Schema::create('payroll_row_additions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_row_id')->constrained('payroll_rows')->onDelete('cascade');
            $table->string('addition_code', 50);
            $table->decimal('amount', 12, 2);
            $table->foreignId('source_reference')->nullable()->constrained('payroll_additions')->onDelete('set null');
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('payroll_row_id');
            $table->index('addition_code');
            $table->index('source_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_row_additions');
    }
};
```

---

### Step 3: Create Models

#### 3.1 EmployeeCompensation Model

**File**: `app/Domain/Payroll/Models/EmployeeCompensation.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompensation extends Model
{
    protected $fillable = [
        'employee_id',
        'base_salary',
        'allowances',
        'effective_from',
        'effective_to',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'allowances' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->effective_to === null;
    }

    public function getTotalAllowancesAttribute(): float
    {
        if (empty($this->allowances)) {
            return 0;
        }

        return (float) array_sum($this->allowances);
    }

    public function getTotalCompensationAttribute(): float
    {
        return (float) $this->base_salary + $this->total_allowances;
    }
}
```

#### 3.2 PayrollDeductionRule Model

**File**: `app/Domain/Payroll/Models/PayrollDeductionRule.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\DeductionBasisType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollDeductionRule extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'basis_type',
        'employee_rate',
        'employer_rate',
        'salary_cap',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'basis_type' => DeductionBasisType::class,
        'employee_rate' => 'decimal:2',
        'employer_rate' => 'decimal:2',
        'salary_cap' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isActive(): bool
    {
        $now = now()->toDateString();

        return $this->effective_from <= $now
            && ($this->effective_to === null || $this->effective_to >= $now);
    }

    public function calculateDeduction(float $salary): array
    {
        $basis = $this->salary_cap && $salary > $this->salary_cap
            ? (float) $this->salary_cap
            : $salary;

        $employeeAmount = $this->employee_rate ? $basis * ($this->employee_rate / 100) : 0;
        $employerAmount = $this->employer_rate ? $basis * ($this->employer_rate / 100) : 0;

        return [
            'basis' => $basis,
            'employee_amount' => round($employeeAmount, 2),
            'employer_amount' => round($employerAmount, 2),
        ];
    }
}
```

#### 3.3 PayrollAddition Model

**File**: `app/Domain/Payroll/Models/PayrollAddition.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use App\Domain\Payroll\Enums\PayrollAdditionCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAddition extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'code',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'code' => PayrollAdditionCode::class,
        'amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

#### 3.4 PayrollRowDeduction Model

**File**: `app/Domain/Payroll/Models/PayrollRowDeduction.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRowDeduction extends Model
{
    protected $fillable = [
        'payroll_row_id',
        'deduction_code',
        'employee_amount',
        'employer_amount',
        'rule_snapshot',
    ];

    protected $casts = [
        'employee_amount' => 'decimal:2',
        'employer_amount' => 'decimal:2',
        'rule_snapshot' => 'array',
    ];

    public function payrollRow(): BelongsTo
    {
        return $this->belongsTo(PayrollRow::class);
    }
}
```

#### 3.5 PayrollRowAddition Model

**File**: `app/Domain/Payroll/Models/PayrollRowAddition.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRowAddition extends Model
{
    protected $fillable = [
        'payroll_row_id',
        'addition_code',
        'amount',
        'source_reference',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payrollRow(): BelongsTo
    {
        return $this->belongsTo(PayrollRow::class);
    }

    public function sourceAddition(): BelongsTo
    {
        return $this->belongsTo(PayrollAddition::class, 'source_reference');
    }
}
```

---

### Step 4: Update Existing Models

#### 4.1 Update PayrollRow Model

**File**: `app/Domain/Payroll/Models/PayrollRow.php`

Add these relationships:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function deductions(): HasMany
{
    return $this->hasMany(PayrollRowDeduction::class);
}

public function additions(): HasMany
{
    return $this->hasMany(PayrollRowAddition::class);
}

public function getTotalEmployeeDeductionsAttribute(): float
{
    return (float) $this->deductions->sum('employee_amount');
}

public function getTotalEmployerDeductionsAttribute(): float
{
    return (float) $this->deductions->sum('employer_amount');
}

public function getTotalAdditionsAttribute(): float
{
    return (float) $this->additions->sum('amount');
}
```

#### 4.2 Update User Model

**File**: `app/Models/User.php`

Add these relationships:

```php
use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Models\PayrollAddition;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

public function compensations(): HasMany
{
    return $this->hasMany(EmployeeCompensation::class, 'employee_id');
}

public function currentCompensation(): HasOne
{
    return $this->hasOne(EmployeeCompensation::class, 'employee_id')
        ->whereNull('effective_to')
        ->latest('effective_from');
}

public function payrollAdditions(): HasMany
{
    return $this->hasMany(PayrollAddition::class, 'employee_id');
}
```

---

### Step 5: Create CalculatePayrollService

**File**: `app/Domain/Payroll/Services/CalculatePayrollService.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Services;

use App\Domain\Payroll\Models\EmployeeCompensation;
use App\Domain\Payroll\Models\PayrollAddition;
use App\Domain\Payroll\Models\PayrollBatch;
use App\Domain\Payroll\Models\PayrollDeductionRule;
use App\Domain\Payroll\Models\PayrollRow;
use App\Domain\Payroll\Models\PayrollRowAddition;
use App\Domain\Payroll\Models\PayrollRowDeduction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalculatePayrollService
{
    public function execute(PayrollBatch $batch): void
    {
        $period = $batch->period;
        $employees = $period->company->users()->whereIn('role', ['EMPLOYEE', 'HR'])->get();

        DB::transaction(function () use ($batch, $period, $employees) {
            foreach ($employees as $employee) {
                $this->calculateForEmployee($batch, $period, $employee);
            }
        });
    }

    protected function calculateForEmployee(PayrollBatch $batch, $period, User $employee): void
    {
        // Get active compensation
        $compensation = $this->getActiveCompensation($employee);

        if (!$compensation) {
            // Skip employees without compensation
            return;
        }

        // Calculate gross
        $baseSalary = (float) $compensation->base_salary;
        $allowances = $compensation->total_allowances;

        // Get additions for this period
        $additions = $this->getAdditions($employee, $period);
        $totalAdditions = $additions->sum('amount');

        $grossAmount = $baseSalary + $allowances + $totalAdditions;

        // Calculate deductions
        $deductions = $this->calculateDeductions($employee, $compensation, $grossAmount);
        $totalEmployeeDeductions = collect($deductions)->sum('employee_amount');

        // Calculate net
        $netAmount = $grossAmount - $totalEmployeeDeductions;

        // Create payroll row
        $row = PayrollRow::create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'gross_amount' => $grossAmount,
            'deduction_amount' => $totalEmployeeDeductions,
            'net_amount' => $netAmount,
        ]);

        // Create deduction details
        foreach ($deductions as $deduction) {
            PayrollRowDeduction::create([
                'payroll_row_id' => $row->id,
                'deduction_code' => $deduction['code'],
                'employee_amount' => $deduction['employee_amount'],
                'employer_amount' => $deduction['employer_amount'],
                'rule_snapshot' => $deduction['snapshot'],
            ]);
        }

        // Create addition details
        foreach ($additions as $addition) {
            PayrollRowAddition::create([
                'payroll_row_id' => $row->id,
                'addition_code' => $addition->code,
                'amount' => $addition->amount,
                'source_reference' => $addition->id,
                'description' => $addition->description,
            ]);
        }
    }

    protected function getActiveCompensation(User $employee): ?EmployeeCompensation
    {
        return $employee->compensations()
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first();
    }

    protected function getAdditions(User $employee, $period): Collection
    {
        return PayrollAddition::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->get();
    }

    protected function calculateDeductions(User $employee, EmployeeCompensation $compensation, float $grossAmount): array
    {
        $companyId = $employee->company_id;
        $deductions = [];

        // Get active deduction rules for company
        $rules = PayrollDeductionRule::where('company_id', $companyId)
            ->where('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            })
            ->get();

        foreach ($rules as $rule) {
            $basis = match ($rule->basis_type->value) {
                'BASE_SALARY' => (float) $compensation->base_salary,
                'CAPPED_SALARY' => min((float) $compensation->base_salary, (float) $rule->salary_cap ?? PHP_FLOAT_MAX),
                'GROSS_SALARY' => $grossAmount,
                default => (float) $compensation->base_salary,
            };

            $calculation = $rule->calculateDeduction($basis);

            $deductions[] = [
                'code' => $rule->code,
                'employee_amount' => $calculation['employee_amount'],
                'employer_amount' => $calculation['employer_amount'],
                'snapshot' => [
                    'rule_id' => $rule->id,
                    'code' => $rule->code,
                    'name' => $rule->name,
                    'basis_type' => $rule->basis_type->value,
                    'employee_rate' => (float) $rule->employee_rate,
                    'employer_rate' => (float) $rule->employer_rate,
                    'salary_cap' => $rule->salary_cap ? (float) $rule->salary_cap : null,
                    'calculation_basis' => $basis,
                    'employee_amount' => $calculation['employee_amount'],
                    'employer_amount' => $calculation['employer_amount'],
                ],
            ];
        }

        return $deductions;
    }
}
```

---

### Step 6: Update FinalizePayrollService

**File**: `app/Domain/Payroll/Services/FinalizePayrollService.php`

Replace the inline calculation with a call to `CalculatePayrollService`:

```php
use App\Domain\Payroll\Services\CalculatePayrollService;

public function __construct(
    private CalculatePayrollService $calculatePayrollService
) {}

public function execute(PayrollPeriod $period, User $actor): PayrollBatch
{
    // ... existing validation code ...

    $batch = PayrollBatch::create([
        'company_id' => $period->company_id,
        'payroll_period_id' => $period->id,
        'total_amount' => 0, // Will be calculated
        'finalized_by' => $actor->id,
        'finalized_at' => now(),
    ]);

    // Use new calculation service
    $this->calculatePayrollService->execute($batch);

    // Update total amount
    $totalAmount = PayrollRow::where('payroll_batch_id', $batch->id)->sum('net_amount');
    $batch->update(['total_amount' => $totalAmount]);

    $period->update([
        'state' => PayrollState::FINALIZED,
        'finalized_by' => $actor->id,
        'finalized_at' => now(),
    ]);

    return $batch;
}
```

---

## Running Migrations

```bash
php artisan migrate
```

Expected output:
```
Running migrations.
2026_01_30_210006_create_employee_compensations_table ........... DONE
2026_01_30_210007_create_payroll_deduction_rules_table .......... DONE
2026_01_30_210008_create_payroll_additions_table ................ DONE
2026_01_30_210009_create_payroll_row_deductions_table ........... DONE
2026_01_30_210010_create_payroll_row_additions_table ............ DONE
```

---

## Post-Implementation Testing

### 1. Enter Employee Compensations

Via Filament admin panel (or Tinker):

```php
use App\Domain\Payroll\Models\EmployeeCompensation;

EmployeeCompensation::create([
    'employee_id' => 1,
    'base_salary' => 5000000,
    'allowances' => [
        'transport' => 500000,
        'meal' => 300000,
    ],
    'effective_from' => '2026-01-01',
    'effective_to' => null,
    'created_by' => 1,
]);
```

### 2. Configure Deduction Rules

```php
use App\Domain\Payroll\Models\PayrollDeductionRule;

// BPJS Kesehatan
PayrollDeductionRule::create([
    'company_id' => 1,
    'code' => 'BPJS_KES',
    'name' => 'BPJS Kesehatan',
    'basis_type' => 'CAPPED_SALARY',
    'employee_rate' => 1.00,
    'employer_rate' => 4.00,
    'salary_cap' => 12000000,
    'effective_from' => '2026-01-01',
]);

// BPJS Ketenagakerjaan JHT
PayrollDeductionRule::create([
    'company_id' => 1,
    'code' => 'BPJS_TK_JHT',
    'name' => 'BPJS TK - JHT',
    'basis_type' => 'CAPPED_SALARY',
    'employee_rate' => 2.00,
    'employer_rate' => 3.70,
    'salary_cap' => 9559600,
    'effective_from' => '2026-01-01',
]);
```

### 3. Add Payroll Additions (Optional)

```php
use App\Domain\Payroll\Models\PayrollAddition;

PayrollAddition::create([
    'employee_id' => 1,
    'payroll_period_id' => 1, // Current draft period
    'code' => 'THR',
    'amount' => 5000000,
    'description' => 'THR Lebaran 2026',
    'created_by' => 1,
]);
```

### 4. Run Full Payroll Cycle

1. Prepare payroll (creates attendance decisions)
2. Review payroll
3. Finalize payroll (triggers CalculatePayrollService)
4. Verify PayrollRow has deductions and additions

### 5. Verify Results

```php
use App\Domain\Payroll\Models\PayrollRow;

$row = PayrollRow::with(['deductions', 'additions'])->find(1);

// Check gross, deductions, net
dump($row->gross_amount);
dump($row->deduction_amount);
dump($row->net_amount);

// Check breakdown
dump($row->deductions->toArray());
dump($row->additions->toArray());

// Verify rule snapshots
dump($row->deductions->first()->rule_snapshot);
```

---

## Validation Checklist

- [ ] All migrations ran successfully
- [ ] Employee compensations created for test employees
- [ ] BPJS deduction rules configured
- [ ] Payroll additions can be added to draft periods
- [ ] Full payroll cycle completes without errors
- [ ] PayrollRow has deduction/addition breakdowns
- [ ] Rule snapshots contain full calculation details
- [ ] Net amount = Gross - Employee Deductions
- [ ] Existing payrolls still viewable (backward compatibility)

---

## Troubleshooting

### Issue: "Cannot run payroll without employee compensation"

**Solution**: Ensure all employees have active compensation records:

```php
$employee->currentCompensation; // Should not be null
```

### Issue: "Deductions not calculated"

**Solution**: Check that deduction rules are active:

```php
PayrollDeductionRule::where('company_id', 1)
    ->where('effective_from', '<=', now())
    ->whereNull('effective_to')
    ->get();
```

### Issue: "Additions not showing in payroll row"

**Solution**: Ensure additions are created for the correct period:

```php
PayrollAddition::where('payroll_period_id', $period->id)->get();
```

---

## Next Steps

After Phase 1 is complete:
- **Phase 2**: Implement leave request workflow
- **Create Filament Resources**: EmployeeCompensationResource, PayrollDeductionRuleResource, PayrollAdditionResource
- **Reports**: Generate detailed payroll reports with deduction breakdowns
