# THR Calculation System - Architecture & Role Mapping

## Overview
This document outlines the clean architecture implementation for the THR (Tunjangan Hari Raya) calculation system, detailing the roles and responsibilities of each layer and component.

## Architecture Layers

### 1. Domain Layer (Business Logic Core)
**Location**: `app/Domain/Payroll/`

#### Value Objects
| File | Role | Responsibility |
|------|------|---------------|
| `ValueObjects/EmployeeTenure.php` | **Employee Tenure Calculator** | • Calculate work duration from dates<br>• Determine eligibility thresholds<br>• Provide formatted tenure display<br>• Handle resignation scenarios |
| `ValueObjects/ThrCalculationResult.php` | **Calculation Result Container** | • Encapsulate THR calculation results<br>• Provide eligibility status<br>• Format monetary amounts<br>• Convert to array for serialization |

#### Business Policies
| File | Role | Responsibility |
|------|------|---------------|
| `Policies/ThrEligibilityPolicy.php` | **Eligibility Rule Engine** | • Validate minimum tenure requirements<br>• Check calculation date constraints<br>• Determine eligibility reasons |
| `Policies/ThrCalculationPolicy.php` | **THR Calculation Rule Engine** | • Implement Indonesian THR regulations<br>• Calculate permanent employee THR<br>• Calculate contract employee THR<br>• Calculate daily/freelance employee THR<br>• Generate calculation explanations<br>• Validate employee types |

#### Domain Services
| File | Role | Responsibility |
|------|------|---------------|
| `Services/ThrDomainService.php` | **Business Logic Orchestrator** | • Coordinate policies and value objects<br>• Execute pure business logic<br>• Validate inputs against business rules<br>• Return domain-specific results |

---

### 2. Infrastructure Layer (Data Access)
**Location**: `app/Infrastructure/Repositories/` & `app/Domain/Payroll/Contracts/`

#### Repository Contracts (Interfaces)
| File | Role | Responsibility |
|------|------|---------------|
| `Contracts/EmployeeRepositoryInterface.php` | **Employee Data Contract** | • Define employee data operations<br>• Specify compensation queries<br>• Set active employee filtering |
| `Contracts/PayrollPeriodRepositoryInterface.php` | **Period Data Contract** | • Define period data operations<br>• Specify company filtering<br>• Set formatting requirements |
| `Contracts/PayrollAdditionRepositoryInterface.php` | **Addition Data Contract** | • Define THR creation operations<br>• Specify duplicate checking<br>• Set batch operations |

#### Repository Implementations
| File | Role | Responsibility |
|------|------|---------------|
| `Infrastructure/Repositories/EloquentEmployeeRepository.php` | **Employee Data Provider** | • Query active employees by company<br>• Load compensation relationships<br>• Check employee existence and status |
| `Infrastructure/Repositories/EloquentPayrollPeriodRepository.php` | **Period Data Provider** | • Query periods by company<br>• Format period options for UI<br>• Validate period ownership |
| `Infrastructure/Repositories/EloquentPayrollAdditionRepository.php` | **Addition Data Provider** | • Create THR records<br>• Check for existing THR<br>• Handle batch creation<br>• Query additions by period |

#### Service Providers
| File | Role | Responsibility |
|------|------|---------------|
| `Providers/RepositoryServiceProvider.php` | **Dependency Injection Manager** | • Bind interfaces to implementations<br>• Configure repository services<br>• Enable dependency injection |

---

### 3. Application Layer (Use Case Orchestration)
**Location**: `app/Application/Payroll/`

#### Data Transfer Objects (DTOs)
| File | Role | Responsibility |
|------|------|---------------|
| `DTOs/ThrCalculationRequest.php` | **Input Data Container** | • Validate calculation parameters<br>• Type-safe data transfer<br>• Array conversion utilities |

#### Application Services
| File | Role | Responsibility |
|------|------|---------------|
| `Services/ThrCalculationApplicationService.php` | **Single THR Calculator** | • Calculate THR for individual employee<br>• Create THR records in database<br>• Generate calculation previews<br>• Handle validation and errors |
| `Services/BulkThrCalculationApplicationService.php` | **Bulk THR Calculator** | • Process multiple employees<br>• Generate bulk previews<br>• Execute batch operations<br>• Transaction management<br>• Error aggregation |
| `Services/ThrPreviewApplicationService.php` | **UI Preview Generator** | • Generate HTML previews<br>• Format preview data<br>• Handle preview errors<br>• Provide period options for UI |

---

### 4. Presentation Layer (User Interface)
**Location**: `app/Filament/Resources/Payroll/`

#### Form Components
| File | Role | Responsibility |
|------|------|---------------|
| `Schemas/PayrollAdditionForm.php` | **THR Form Handler** | • Render THR calculation form<br>• Handle user interactions<br>• Trigger calculation actions<br>• Display results to user |

#### Table Components
| File | Role | Responsibility |
|------|------|---------------|
| `Tables/PayrollAdditionsTable.php` | **THR Table Manager** | • Display THR records<br>• Handle bulk actions<br>• Show calculation previews<br>• Manage table operations |

#### Resource Coordinators
| File | Role | Responsibility |
|------|------|---------------|
| `PayrollAdditionResource.php` | **Resource Coordinator** | • Configure navigation<br>• Set up pages<br>• Define access controls<br>• Coordinate form and table |

#### Page Handlers
| File | Role | Responsibility |
|------|------|---------------|
| `Pages/ListPayrollAdditions.php` | **List Page Manager** | • Display addition listings<br>• Handle list actions |
| `Pages/CreatePayrollAddition.php` | **Creation Page Manager** | • Handle THR creation<br>• Set default values<br>• Process form submissions |
| `Pages/ViewPayrollAddition.php` | **View Page Manager** | • Display THR details<br>• Show calculation breakdown |
| `Pages/EditPayrollAddition.php` | **Edit Page Manager** | • Handle THR modifications<br>• Validate changes |

---

## Data Flow & Interactions

### THR Calculation Flow
```
1. User Input (Presentation Layer)
   ↓
2. Form Validation (Presentation Layer)
   ↓
3. Application Service (Application Layer)
   ↓
4. Repository Query (Infrastructure Layer)
   ↓
5. Domain Service (Domain Layer)
   ↓
6. Business Rules (Domain Layer)
   ↓
7. Calculation Result (Domain Layer)
   ↓
8. Database Storage (Infrastructure Layer)
   ↓
9. UI Response (Presentation Layer)
```

### Bulk THR Processing Flow
```
1. Bulk Action Trigger (Presentation Layer)
   ↓
2. Preview Generation (Application Layer)
   ↓ 
3. User Confirmation (Presentation Layer)
   ↓
4. Batch Processing (Application Layer)
   ↓
5. Transaction Management (Infrastructure Layer)
   ↓
6. Individual Calculations (Domain Layer)
   ↓
7. Batch Results (Application Layer)
   ↓
8. Success/Error Notifications (Presentation Layer)
```

---

## Dependency Rules

### ✅ Allowed Dependencies
- **Domain Layer**: No external dependencies (pure business logic)
- **Infrastructure Layer**: Can depend on Domain contracts
- **Application Layer**: Can depend on Domain and Infrastructure
- **Presentation Layer**: Can depend on Application (not directly on Domain or Infrastructure)

### ❌ Forbidden Dependencies
- Domain Layer CANNOT depend on Infrastructure or Application
- Infrastructure Layer CANNOT depend on Application or Presentation
- Application Layer CANNOT depend on Presentation

---

## Testing Strategy

### Unit Tests (Domain Layer)
| Test File | Target | Responsibility |
|-----------|--------|---------------|
| `EmployeeTenureTest.php` | Value Objects | Test tenure calculations without external dependencies |
| `ThrCalculationPolicyTest.php` | Business Rules | Test THR calculation formulas |
| `ThrDomainServiceTest.php` | Domain Logic | Test business logic orchestration |

### Integration Tests (Application Layer)
| Test File | Target | Responsibility |
|-----------|--------|---------------|
| `ThrCalculationApplicationServiceTest.php` | Use Cases | Test application service with database |

### Feature Tests (End-to-End)
- Test complete user workflows
- Verify UI interactions work correctly
- Ensure proper error handling

---

## Key Principles Applied

### 1. **Single Responsibility Principle**
- Each class has one clear responsibility
- Business rules separated from data access
- UI concerns separated from business logic

### 2. **Open/Closed Principle**
- Easy to add new employee types via policy extension
- Repository pattern allows different data sources
- New calculation methods can be added without modification

### 3. **Liskov Substitution Principle**
- Repository implementations are interchangeable
- Application services work with any valid repository

### 4. **Interface Segregation Principle**
- Repository interfaces are focused and specific
- No forced dependencies on unused methods

### 5. **Dependency Inversion Principle**
- High-level modules depend on abstractions
- Concrete implementations depend on abstractions
- Framework-agnostic business logic

---

## Benefits Achieved

### 🎯 **Maintainability**
- Clear separation of concerns
- Easy to locate and modify business rules
- Consistent patterns throughout codebase

### 🧪 **Testability**
- Pure business logic is easily unit tested
- Repository pattern enables easy mocking
- Clear interfaces simplify test setup

### 🔄 **Flexibility**
- Easy to swap data sources (databases, APIs)
- Business logic can be reused in different contexts
- UI framework changes don't affect business logic

### 📚 **Clarity**
- Business rules are explicit and documented
- Data access patterns are consistent
- Clear boundaries between layers

This architecture ensures the THR calculation system is robust, maintainable, and follows industry best practices for clean architecture implementation.