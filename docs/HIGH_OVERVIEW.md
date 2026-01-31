# High-Level Project Overview

## Project Name (Working)
**Payroll Core — Attendance → Payroll Export**

---

## 1. What This Product Is

This project is a **payroll decision system**, not an HR dashboard and not a calculator.

Its purpose is to:
- Convert raw attendance and leave facts into **explicit payroll decisions**
- Allow business owners to **review and consciously approve** payroll outcomes
- Freeze payroll results into **defensible, immutable records**

Once payroll is finalized, the system guarantees:
> “This amount was approved by authority, based on data available at that time.”

---

## 2. Who This Product Is For

### Buyer
- Business Owner / Director
- Ultimately responsible for company money and payroll disputes

### Users
- HR staff (operational preparation)
- Owner (review, override, final approval)

### Not the Target
- Enterprise HR departments
- Companies seeking tax or accounting automation

---

## 3. Core Problem Being Solved

Most SMEs handle payroll with:
- Excel
- Informal HR judgment
- Last-minute approvals
- Silent recalculations

This creates:
- Payroll disputes
- Overpayment or underpayment risk
- No clear accountability
- No defensible audit trail

This system solves **payroll risk**, not payroll convenience.

---

## 4. Core Product Promise

> **Payroll decisions are explicit, reviewable, and irreversible once approved.**

The system protects owners by ensuring:
- No hidden changes
- No silent recalculation
- No unclear responsibility

---

## 5. Conceptual Architecture

### Fact → Decision → Commitment

1. **Facts**
   - Raw attendance records
   - Approved leave

2. **Decisions**
   - Attendance classification
   - Payability
   - Deductions
   - Overrides (owner-approved)

3. **Commitment**
   - Payroll batch
   - Payroll rows
   - Frozen totals

Each layer is immutable once passed.

---

## 6. Payroll Lifecycle (State-Driven)

Payroll moves through fixed states:

- **DRAFT** — HR prepares, system computes decisions
- **REVIEW** — Owner inspects, overrides if needed
- **FINALIZED** — Owner commits to payment
- **LOCKED** — Historical record, audit-only

There are **no backward transitions**.

---

## 7. Authority Model

| Role | Responsibility |
|------|---------------|
| Employee | Provide attendance facts |
| Supervisor | Approve attendance / leave |
| HR | Prepare payroll, request overrides |
| Owner | Approve overrides, finalize payroll |
| System | Apply rules deterministically |

Only the owner can create financial commitment.

---

## 8. System Boundaries (Explicit Non-Goals)

This project does **not**:
- Pay salaries
- Calculate taxes
- Handle accounting journals
- Optimize payroll costs

It outputs **defensible payroll results**, nothing more.

---

## 9. Technology Direction

- Backend: **Laravel**
- Admin UI: **Filament**
- Architecture: **Domain-first, service-driven**
- Business logic: **Domain Services**
- UI role: **Orchestration only**

---

## 10. Definition of Success (v1)

The system is successful when:
- Payroll can be prepared from raw attendance
- Owners can review and override explicitly
- Payroll can be finalized and frozen
- Finance receives a trusted export
- Any payroll dispute can be answered without recalculation

---

**End of High-Level Overview**