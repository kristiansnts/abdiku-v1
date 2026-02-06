HRIS SaaS Production Readiness Checklist (V1)

1. Core Business Logic (Critical)

If any of these fail, your system is not production-ready.

Attendance
	•	Employee can clock in and clock out from mobile
	•	AttendanceRaw records are created correctly
	•	Late logic is applied consistently based on company rules
	•	Leave and sick records affect attendance correctly
	•	Holiday logic prevents false absences
	•	No impossible states:
	•	clock_out without clock_in
	•	multiple clock_in on same day

Correction Flow
	•	Employee can submit attendance correction request
	•	Supervisor/HR can approve or reject request
	•	Approved request creates attendance_override
	•	HR manual override also creates attendance_override
	•	Payroll reads:
	•	attendance_raw
	•	attendance_override
	•	attendance_request is never used directly in payroll

Payroll
	•	Payroll batch can be created per period
	•	Attendance decisions are generated from raw + overrides
	•	Salary calculation uses:
	•	base salary
	•	payable days
	•	deductions
	•	additions
	•	Payroll results are stored as immutable rows
	•	Payroll batch can be locked

⸻

2. Data Integrity & Audit Trail (Critical for trust)

Payroll without audit = lawsuit risk.
	•	All overrides store:
	•	who changed
	•	when changed
	•	old value
	•	new value
	•	reason
	•	Payroll batches are immutable after lock
	•	No deletion of:
	•	attendance_raw
	•	attendance_override
	•	payroll rows
	•	Soft delete only, or no delete at all

⸻

3. Roles & Permissions

Test with real role boundaries.

Owner
	•	Can see all employees
	•	Can approve overrides
	•	Can run payroll
	•	Can lock payroll

HR
	•	Can manage employees
	•	Can review requests
	•	Cannot finalize payroll without owner (if rule exists)

Employee
	•	Can only see own data
	•	Can clock in/out
	•	Can request corrections
	•	Can see own payslip

⸻

4. Payroll Accuracy Tests (Must-pass scenarios)

Create test employees and simulate:

Scenario tests
	•	Full attendance for entire period
	•	1 late day
	•	1 absent day
	•	Paid leave inside quota
	•	Leave beyond quota (becomes unpaid)
	•	Sick with doctor note (paid)
	•	Sick without doctor note (unpaid)
	•	Attendance correction approved
	•	Payroll recalculated after override

All totals must match manual calculation.

⸻

5. Mobile Attendance Reliability
	•	Clock in works online
	•	Clock in works offline and syncs later
	•	Pending sync status visible
	•	Duplicate clock prevented
	•	GPS or geofence validated (if enabled)

⸻

6. API Stability
	•	All endpoints return consistent JSON structure
	•	Proper HTTP status codes:
	•	200 success
	•	400 validation error
	•	401 unauthorized
	•	403 forbidden
	•	Authentication required on all protected endpoints
	•	No sensitive data exposed in API

⸻

7. Performance & Scaling (for 1,000+ employees)

You don’t need complex infrastructure yet, but basics must exist.
	•	Indexes on:
	•	attendance_raw(employee_id, date)
	•	attendance_override(attendance_raw_id)
	•	payroll_rows(payroll_batch_id)
	•	Payroll calculation runs under:
	•	30 seconds for 1,000 employees
	•	Background job for:
	•	payroll preparation
	•	heavy reports

⸻

8. Security Basics
	•	Passwords hashed (bcrypt/argon)
	•	No plain text tokens in database
	•	Rate limit on login
	•	HTTPS enforced in production
	•	Environment variables for secrets

⸻

9. Operational Readiness

You must be able to operate this for a real company.
	•	Admin can create company/tenant
	•	Admin can add employees
	•	Admin can assign base salary
	•	Payroll can be exported (CSV/Excel)
	•	System time zone is correct
	•	Backup process exists
