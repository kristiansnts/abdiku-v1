# Complete System - User Roles & Permissions Mapping

## Overview
This document outlines comprehensive user roles and their permissions across all modules of the HR/Payroll management system, defining access levels and responsibilities for each user type.

---

## System Modules Overview

1. **Employee Management**
2. **Attendance Management** 
3. **Leave Management**
4. **Payroll Management**
5. **Compensation Management**
6. **THR (Holiday Allowance)**
7. **Company Management**
8. **User Management**
9. **Reports & Analytics**
10. **System Configuration**

---

## User Role Hierarchy

```
🏢 Owner (Company Owner)
├── 👔 HR Manager
│   ├── 📋 HR Staff
│   ├── 💰 Payroll Administrator
│   └── 📊 HR Analyst
├── 👨‍💼 Department Manager
│   └── 👥 Team Lead
└── 👨‍💼 Employee (Regular Staff)
```

---

## Detailed Role Permissions

### 🏢 **Owner (Company Owner)**
**Authority Level**: Full System Control

#### **Employee Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View all employees | ✅ **FULL** | Complete access to all employee data |
| Create employees | ✅ **FULL** | Can add new employees |
| Edit employee profiles | ✅ **FULL** | Can modify any employee information |
| Deactivate/terminate employees | ✅ **FULL** | Can change employment status |
| View salary information | ✅ **FULL** | Access to all compensation data |
| Assign departments/roles | ✅ **FULL** | Can manage organizational structure |

#### **Attendance Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View all attendance records | ✅ **FULL** | Can see attendance for all employees |
| Modify attendance records | ✅ **FULL** | Can edit/correct attendance data |
| Approve attendance corrections | ✅ **FULL** | Final authority on attendance disputes |
| Configure attendance rules | ✅ **FULL** | Can set work hours, overtime rules |
| Generate attendance reports | ✅ **FULL** | Access to all attendance analytics |

#### **Leave Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View all leave requests | ✅ **FULL** | Can see leave requests from all employees |
| Approve/reject leave requests | ✅ **FULL** | Final approval authority |
| Override leave approvals | ✅ **FULL** | Can reverse manager decisions |
| Configure leave policies | ✅ **FULL** | Can set leave types, quotas, rules |
| View leave balances | ✅ **FULL** | Access to all employee leave balances |

#### **Payroll Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View all payroll data | ✅ **FULL** | Complete payroll visibility |
| Process payroll | ✅ **FULL** | Can run payroll calculations |
| Approve payroll | ✅ **FULL** | Final payroll approval |
| Modify payroll amounts | ✅ **FULL** | Can adjust salaries and deductions |
| Configure payroll rules | ✅ **FULL** | Can set calculation rules |
| Generate payroll reports | ✅ **FULL** | Access to all payroll analytics |

#### **System Administration**
| Action | Permission | Notes |
|--------|------------|-------|
| User management | ✅ **FULL** | Can create/modify/deactivate users |
| Role assignment | ✅ **FULL** | Can assign roles and permissions |
| Company settings | ✅ **FULL** | Can modify company configurations |
| System backups | ✅ **FULL** | Can manage data backups |
| Integration settings | ✅ **FULL** | Can configure external integrations |

---

### 👔 **HR Manager**
**Authority Level**: HR Operations Management

#### **Employee Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View all employees | ✅ **FULL** | Complete access to employee data |
| Create employees | ✅ **FULL** | Can onboard new employees |
| Edit employee profiles | ✅ **FULL** | Can modify employee information |
| Deactivate employees | ⚠️ **LIMITED** | Can recommend, needs owner approval |
| View salary information | ✅ **FULL** | Access to compensation data |
| Assign departments/roles | ✅ **FULL** | Can manage org structure |

#### **Attendance Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View all attendance | ✅ **FULL** | Can see all attendance records |
| Modify attendance | ✅ **FULL** | Can edit attendance data |
| Approve corrections | ✅ **FULL** | Can approve attendance corrections |
| Configure basic rules | ⚠️ **LIMITED** | Can modify basic attendance settings |
| Generate reports | ✅ **FULL** | Access to attendance analytics |

#### **Leave Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View all leave requests | ✅ **FULL** | Can see all leave requests |
| Approve/reject requests | ✅ **FULL** | Can make leave decisions |
| Manage leave policies | ⚠️ **LIMITED** | Can suggest policy changes |
| View leave balances | ✅ **FULL** | Access to all leave balances |
| Generate leave reports | ✅ **FULL** | Can create leave analytics |

#### **Payroll Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View payroll data | ✅ **FULL** | Can see all payroll information |
| Process payroll | ✅ **FULL** | Can run payroll calculations |
| Approve payroll | ⚠️ **LIMITED** | Can approve up to certain amounts |
| Modify amounts | ⚠️ **LIMITED** | Can adjust within defined limits |
| Generate reports | ✅ **FULL** | Access to payroll reports |

---

### 📋 **HR Staff**
**Authority Level**: Operational Support

#### **Employee Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View assigned employees | ⚠️ **LIMITED** | Can see employees in assigned departments |
| Create employee records | ✅ **FULL** | Can add new employees |
| Edit basic information | ⚠️ **LIMITED** | Can update contact info, personal details |
| View public information | ✅ **FULL** | Can see non-sensitive employee data |
| Cannot view salaries | ❌ **DENY** | No access to compensation data |

#### **Attendance Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View attendance (assigned) | ⚠️ **LIMITED** | Can see attendance for assigned employees |
| Input attendance data | ✅ **FULL** | Can enter attendance records |
| Submit corrections | ✅ **FULL** | Can request attendance corrections |
| Cannot approve | ❌ **DENY** | Cannot approve attendance changes |
| Basic reports | ⚠️ **LIMITED** | Limited reporting access |

#### **Leave Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View leave requests (assigned) | ⚠️ **LIMITED** | Can see requests for assigned employees |
| Process leave applications | ✅ **FULL** | Can input and track leave requests |
| Cannot approve | ❌ **DENY** | Cannot make approval decisions |
| Check leave balances | ⚠️ **LIMITED** | Can view for assigned employees |

---

### 💰 **Payroll Administrator**
**Authority Level**: Payroll Operations

#### **Payroll Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View payroll data | ✅ **FULL** | Can see all payroll information |
| Process payroll | ✅ **FULL** | Can run payroll calculations |
| Cannot approve | ❌ **DENY** | Cannot approve final payroll |
| Generate reports | ✅ **FULL** | Access to detailed payroll reports |
| Configure deductions | ⚠️ **LIMITED** | Can set up standard deductions |

#### **Compensation Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View compensation data | ✅ **FULL** | Can see all salary information |
| Input salary changes | ⚠️ **LIMITED** | Can input pre-approved changes |
| Cannot modify rates | ❌ **DENY** | Cannot change salary rates |
| Track compensation history | ✅ **FULL** | Can view compensation changes |

---

### 📊 **HR Analyst**
**Authority Level**: Analytics and Reporting

#### **Reports & Analytics**
| Action | Permission | Notes |
|--------|------------|-------|
| Generate all reports | ✅ **FULL** | Can create comprehensive reports |
| Access analytics dashboard | ✅ **FULL** | Can view HR metrics and KPIs |
| Export data | ⚠️ **LIMITED** | Can export aggregated data only |
| Cannot modify data | ❌ **DENY** | Read-only access to all information |

---

### 👨‍💼 **Department Manager**
**Authority Level**: Department Management

#### **Employee Management (Department)**
| Action | Permission | Notes |
|--------|------------|-------|
| View department employees | ✅ **FULL** | Can see all employees in their department |
| Request employee changes | ⚠️ **LIMITED** | Can request modifications through HR |
| View basic salary info | ⚠️ **LIMITED** | Can see salary grades, not exact amounts |

#### **Attendance Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View department attendance | ✅ **FULL** | Can see attendance for their team |
| Approve overtime | ✅ **FULL** | Can approve overtime for their team |
| Request corrections | ✅ **FULL** | Can request attendance corrections |

#### **Leave Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View department leave | ✅ **FULL** | Can see leave requests in their department |
| Approve/reject leave | ✅ **FULL** | Can make leave decisions for their team |
| View leave calendar | ✅ **FULL** | Can see department leave calendar |

---

### 👥 **Team Lead**
**Authority Level**: Team Supervision

#### **Team Management**
| Action | Permission | Notes |
|--------|------------|-------|
| View team members | ✅ **FULL** | Can see team member information |
| Approve timesheets | ✅ **FULL** | Can approve team timesheets |
| Recommend leave | ✅ **FULL** | Can recommend leave approval |
| Cannot approve salary changes | ❌ **DENY** | Cannot modify compensation |

---

### 👨‍💼 **Employee (Regular Staff)**
**Authority Level**: Self-Service

#### **Personal Information**
| Action | Permission | Notes |
|--------|------------|-------|
| View own profile | ✅ **FULL** | Can see their own employee information |
| Update personal info | ⚠️ **LIMITED** | Can update contact details only |
| View own salary | ✅ **FULL** | Can see their own compensation |
| Cannot modify salary | ❌ **DENY** | Cannot change compensation |

#### **Attendance**
| Action | Permission | Notes |
|--------|------------|-------|
| View own attendance | ✅ **FULL** | Can see their attendance records |
| Clock in/out | ✅ **FULL** | Can record attendance |
| Request corrections | ✅ **FULL** | Can request attendance corrections |
| View overtime | ✅ **FULL** | Can see their overtime records |

#### **Leave**
| Action | Permission | Notes |
|--------|------------|-------|
| Submit leave requests | ✅ **FULL** | Can request leave |
| View own leave history | ✅ **FULL** | Can see their leave records |
| Check leave balance | ✅ **FULL** | Can view available leave days |
| Cancel pending requests | ✅ **FULL** | Can cancel unprocessed requests |

#### **Payroll & THR**
| Action | Permission | Notes |
|--------|------------|-------|
| View own payslips | ✅ **FULL** | Can see their salary information |
| Download payslips | ✅ **FULL** | Can download pay statements |
| View THR calculations | ✅ **FULL** | Can see their THR details |
| Request payroll clarification | ✅ **FULL** | Can ask questions about pay |

---

## Complete Permission Matrix

| Module | Feature | Owner | HR Mgr | HR Staff | Payroll | Analyst | Dept Mgr | Team Lead | Employee |
|--------|---------|-------|--------|----------|---------|---------|----------|-----------|----------|
| **Employee** | View All | ✅ | ✅ | ⚠️ | ❌ | ✅ | ⚠️ | ⚠️ | ❌ |
| **Employee** | Create/Edit | ✅ | ✅ | ⚠️ | ❌ | ❌ | ❌ | ❌ | ⚠️ |
| **Employee** | Terminate | ✅ | ⚠️ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Attendance** | View All | ✅ | ✅ | ⚠️ | ❌ | ✅ | ⚠️ | ⚠️ | ❌ |
| **Attendance** | Modify | ✅ | ✅ | ✅ | ❌ | ❌ | ⚠️ | ⚠️ | ⚠️ |
| **Attendance** | Approve | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| **Leave** | View All | ✅ | ✅ | ⚠️ | ❌ | ✅ | ⚠️ | ⚠️ | ❌ |
| **Leave** | Approve | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ⚠️ | ❌ |
| **Leave** | Configure | ✅ | ⚠️ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Payroll** | View All | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Payroll** | Process | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Payroll** | Approve | ✅ | ⚠️ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **THR** | Calculate | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **THR** | Approve | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Compensation** | View All | ✅ | ✅ | ❌ | ✅ | ✅ | ⚠️ | ❌ | ❌ |
| **Compensation** | Modify | ✅ | ⚠️ | ❌ | ⚠️ | ❌ | ❌ | ❌ | ❌ |
| **Reports** | Generate | ✅ | ✅ | ⚠️ | ✅ | ✅ | ⚠️ | ⚠️ | ⚠️ |
| **System** | Configure | ✅ | ⚠️ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Users** | Manage | ✅ | ⚠️ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Legend:**
- ✅ **FULL**: Complete access
- ⚠️ **LIMITED**: Restricted access (department/assigned only, or with approval)
- ❌ **DENY**: No access

---

## Security & Compliance Framework

### **Data Access Controls**
- **Company Isolation**: Users can only access data from their company
- **Department Filtering**: Department managers see only their department data
- **Role-Based Menus**: UI adapts based on user permissions
- **Field-Level Security**: Sensitive fields hidden based on role

### **Approval Workflows**

#### **Leave Approval Flow**
```
Employee → Team Lead → Department Manager → HR Manager → Owner (if needed)
```

#### **Salary Change Flow**
```
HR Staff Input → HR Manager Review → Owner Approval → Payroll Processing
```

#### **Attendance Correction Flow**
```
Employee Request → Team Lead Review → HR Approval → System Update
```

### **Audit & Compliance**
- **Action Logging**: All sensitive actions logged with user, timestamp, and reason
- **Data Retention**: Historical data preserved for compliance
- **Privacy Protection**: Personal data access restricted by role
- **Indonesian Labor Law**: System enforces local employment regulations

### **Technical Implementation**
- **Multi-tenancy**: Company-level data isolation
- **Role-Based Access Control (RBAC)**: Permission-based feature access
- **Session Management**: Role validation on every request
- **API Security**: Endpoint protection based on user permissions

This comprehensive role mapping ensures secure, compliant, and efficient management of all HR and payroll operations across the entire system.