# TRD — Technical Requirements Document

## Payroll Core (Attendance → Payroll Export)

> **Purpose**: This TRD defines the exact technical steps and execution order for building the Payroll Core system. This document is written so an AI or engineer can execute it sequentially **without making product decisions**.

---

## 0. System Constraints (Non‑Negotiable)

* System is **decision‑recording**, not a calculator
* Payroll is **period‑rule‑based**
* Payroll history is **immutable once finalized**
* Owner is the **only authority** that can finalize payroll
* Business logic lives in **Domain Services**, never in UI or models

---

## 1. Execution Order (Strict)

The system MUST be built in this order:

1. Database schema (migrations) — from ERD
2. Domain enums & constants
3. Domain models (fact storage only)
4. Domain services (business decisions)
5. Filament resources (orchestration only)

❌ Do NOT implement UI, auth flows, or integrations before step 4.

---

## 2. Database Schema (Migrations)

### 2.1 companies

```sql
id (pk)
name
created_at
```

---

### 2.2 users

```sql
id (pk)
company_id (fk)
name
role ENUM('EMPLOYEE','HR','OWNER')
created_at
```

---

### 2.3 payroll_periods

Represents one payroll cycle.

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

Rules:

* Only one ACTIVE period per company
* State transitions are enforced in services, not DB

---

### 2.4 attendance_raw

Immutable factual records.

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

Rules:

* Records are never updated, only appended

---

### 2.5 leave_records

Approved human decisions.

```sql
id (pk)
company_id (fk)
employee_id (fk)
date DATE
leave_type ENUM('PAID','UNPAID','SICK_PAID','SICK_UNPAID')
approved_by (fk users.id)
created_at
```

---

### 2.6 attendance_decisions

System‑declared interpretation of attendance.

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

Rules:

* One decision per employee per date per period
* Recomputed freely until FINALIZED

---

### 2.7 attendance_overrides

Explicit owner interventions.

```sql
id (pk)
attendance_decision_id (fk)
old_classification
new_classification
reason TEXT
overridden_by (fk users.id)
overridden_at DATETIME
```

---

### 2.8 payroll_batches

Frozen payroll commitment.

```sql
id (pk)
company_id (fk)
payroll_period_id (fk)
total_amount DECIMAL
finalized_by (fk users.id)
finalized_at DATETIME
```

---

### 2.9 payroll_rows

Per‑employee frozen result.

```sql
id (pk)
payroll_batch_id (fk)
employee_id (fk)
gross_amount DECIMAL
deduction_amount DECIMAL
net_amount DECIMAL
```

Rules:

* Rows are immutable after creation

---

## 3. Domain Enums (Code‑Level)

Must exist BEFORE services.

* PayrollState
* AttendanceClassification
* DeductionType
* Role

All enums must be **typed**, never strings.

---

## 4. Domain Services (Business Logic)

### 4.1 PreparePayrollService

Responsibilities:

* Read raw attendance + leave
* Apply company rules
* Generate attendance_decisions
* Detect anomalies

Constraints:

* Allowed only in DRAFT
* Callable by HR
* Idempotent

---

### 4.2 ReviewPayrollService

Responsibilities:

* Validate readiness
* Freeze raw inputs
* Move state DRAFT → REVIEW

---

### 4.3 ApproveOverrideService

Responsibilities:

* Owner‑only
* Apply explicit override
* Record before/after

---

### 4.4 FinalizePayrollService

Responsibilities:

* Owner‑only
* Create payroll_batch
* Create payroll_rows
* Freeze decisions
* Move state → FINALIZED

Must run in DB transaction.

---

## 5. Filament Usage Rules

Filament is allowed to:

* Display state
* Trigger service calls
* Confirm destructive actions

Filament is NOT allowed to:

* Mutate state directly
* Calculate payroll
* Apply rules

---

## 6. Validation Checklist (Must Pass)

* [ ] HR cannot finalize payroll
* [ ] Finalized payroll cannot change
* [ ] All payroll rows trace to decisions
* [ ] Overrides always record authority
* [ ] Rule changes do not affect history

---

## 7. Definition of DONE (v1)

The system is considered complete when:

* A payroll period can be prepared
* Reviewed by owner
* Finalized into frozen payroll rows
* Exported for finance
* Fully auditable per employee

---

**END OF TRD**
