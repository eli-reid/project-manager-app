# Sprint 2: Payroll Infrastructure - Codebase Analysis

## Executive Summary

The **project-manager-app** uses a clean domain-driven design with Laravel 12, Livewire 4, and Flux UI. Payroll backend scaffolding is now implemented and validated with passing domain tests.

## Progress Update (2026-04-10)

✅ **Completed in Sprint 2 payroll backbone**:
- Domain payroll models and migrations (PayRate, BurdenRate, PayrollPeriod, PayRun, PayrollRecord, PayrollCorrection)
- Payroll permissions registration and policy scaffolding
- Payroll calculation, processing, and reporting services
- Payroll CSV generation and period/payrun summary service methods
- Payroll lifecycle test coverage (28 passing tests in PayrollDomainScaffoldTest)

✅ **Already Exist**:
- Timecard tracking (timecards, entries, workflow)
- Role-based authorization system (Roles, Permissions)
- Financial reporting framework
- Service layer patterns
- Pest testing infrastructure

❌ **Need to Build**:
- Payroll-to-project financial sync
- Monthly financial performance report
- Labor and material cost analysis reports
- Drill-down dimensions and PDF parity for exports
- Reconciliation and snapshot stability gate tests

---

## 1. Existing Models & Database Foundation

### Core Time Tracking Models

**Timecard Model** (`app/Domains/Timecards/Models/Timecard.php`)
- ULIDs for primary keys (not auto-increment)
- Weekly scope: `week_starting`, `week_ending`, `total_hours`
- Status workflow: draft → submitted → approved/rejected
- Soft deletes for audit trail
- Relationships: User, approver, rejector, entries

**TimecardEntry Model** (`app/Domains/Timecards/Models/TimecardEntry.php`)
- Individual work entries per day
- Links to: Timecard, User, Project, custom project name
- Fields: `date`, `start_time`, `hours`, `notes`
- Soft deletes enabled

**Invoice Model** (`app/Domains/Invoices/Models/Invoice.php`)
- Vendor/material invoices (not payroll, but shows financial patterns)
- Status: Draft → Pending → Verified → Paid
- Includes tax calculations and timestamp tracking
- Line items with sort order

**User Model** (`app/Core/Identity/Models/User.php`)
- No pay rate fields yet
- ULID-based
- Two-factor auth ready
- Role relationship supported

### Authorization Models

**Role Model** (`app/Core/Auth/Role/Models/Role.php`)
- Name, description, active status, built-in flag
- Access level (0-100) for hierarchical support
- Built-in roles (Admin, User) protected from deletion
- Many-to-many with User and Permission

**Permission Model** (`app/Core/Auth/Permission/Models/Permission.php`)
- Format: `resource.action` (e.g., `payroll.view`, `payroll.process`)
- Includes label and description
- Many-to-many with Role

---

## 2. Existing Services & Business Logic

### Timecard Domain Services

| Service | Purpose |
|---------|---------|
| **TimecardLifecycleService** | State transitions (draft → submitted → approved/rejected) |
| **TimecardWeekService** | Week boundary calculations |
| **TimecardReminderService** | Notification handling |
| **TimecardNotificationRecipientService** | Determines notification recipients |
| **TimecardEntrySyncService** | Entry synchronization logic |

### Financial Services

**Reports Domain** - Already has:
- `FinancialReports` Livewire component
- Export to CSV functionality
- Time range filtering (`fromDate`, `toDate`)
- Permission system: `financial-reports.view`, `financial-reports.export`

---

## 3. Permission & Authorization System

### Permission Structure (Resource.Action Format)

```php
'resource' => 'payroll',
'action' => 'view',  // e.g., 'payroll.view' or 'payroll.process'
```

### Existing Timecard Permissions (Reference Pattern)

```javascript
timecards.view          // View own
timecards.view-all      // View all users'
timecards.create        // Create
timecards.edit          // Edit
timecards.submit        // Submit for approval
timecards.approve       // Approve
timecards.reject        // Reject
timecards.delete        // Delete
```

### Authorization Pattern (In Policies)

```php
// app/Domains/{Domain}/Policies/{Model}Policy.php

public function before(User $user, string $ability): ?bool
{
    if ($user->isAdmin()) {
        return true;  // Admin bypass
    }
    return null;  // Continue to specific checks
}

public function viewAll(User $user): bool
{
    return $user->hasPermission('payroll.view-all');
}
```

### Testing Authorization

```php
$user = userWithTimecardDomainPermissions(['timecards.approve']);
actingAs($user)->post(...)->assertSuccessful();
```

---

## 4. Testing Infrastructure

### Pest Configuration

```php
// tests/Pest.php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('../app/Core', '../app/Domains');
```

### Test Helper Pattern

**Location**: `app/Domains/{Domain}/Tests/Feature/` (within domain)

**Creating Targeted Users**:
```php
function userWithTimecardDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();
    
    $user = User::factory()->create(['is_admin' => false]);
    $role = Role::query()->create([...]);
    
    // Sync permissions to role
    $permissionIds = Permission::query()
        ->whereIn([['resource', 'action'], ...])
        ->pluck('id');
    
    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);
    
    return $user->fresh();
}
```

### Feature Test Example

```php
it('allows user with payroll.view to see payroll', function () {
    $user = userWithPayrollDomainPermissions(['payroll.view']);
    actingAs($user)->get(route('payroll.index'))->assertOk();
});
```

---

## 5. Database Design Patterns

### Table Conventions

```php
// Primary keys: ULIDs (not auto-increment)
$table->ulid('id')->primary();

// Money: Use decimal with 2 places
$table->decimal('gross_pay', 10, 2);

// Audit trails: Soft deletes
$table->softDeletes();

// Timestamps (automatic)
$table->timestamps();  // created_at, updated_at

// Foreign keys: Use constrained()
$table->foreignUlid('user_id')->constrained('users');

// Indexes
$table->unique(['resource', 'action']);
$table->index(['is_active']);
```

### Casts (Model Type Casting)

```php
protected function casts(): array
{
    return [
        'gross_pay' => 'decimal:2',
        'payment_date' => 'date',
        'processed_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
```

---

## 6. Domain-Driven Architecture

### Standard Domain Structure

```
app/Domains/Payroll/
├── Models/
│   ├── PayRate.php
│   ├── PayRun.php
│   └── PayrollRecord.php
├── Services/
│   ├── PayrollCalculationService.php
│   ├── PayrollProcessingService.php
│   └── PayrollReportService.php
├── Policies/
│   └── PayrollPolicy.php
├── Permissions/
│   └── PayrollPermissions.php
├── Database/
│   ├── Factories/
│   ├── Migrations/
│   └── Seeders/
├── Livewire/
│   ├── Admin/
│   │   ├── PayrollProcessor/
│   │   ├── PayRateManager/
│   │   └── PayrollReports/
│   └── User/
│       └── PayrollHistory/
├── Resources/Views/
│   └── livewire/
├── Routes/
│   ├── web.php
│   ├── admin.php
│   ├── api.php
│   └── mobile.php
├── Tests/Feature/
├── Providers/
│   └── PayrollServiceProvider.php
```

### Service Provider Registration Pattern

```php
// app/Domains/Payroll/Providers/PayrollServiceProvider.php

public function boot(PermissionRegistryContract $permissionRegistry): void
{
    $this->registerPermissions($permissionRegistry);
    $this->registerAuthorization();
    $this->registerInfrastructure();
    $this->registerUIComponents();
    $this->registerRoutes();
}

private function registerPermissions(PermissionRegistryContract $registry): void
{
    $registry->registerPermissions(PayrollPermissions::all());
}

private function registerAuthorization(): void
{
    Gate::policy(PayrollRecord::class, PayrollPolicy::class);
}
```

---

## 7. Foundational Patterns to Follow

### Service Injection

```php
class PayrollProcessingService
{
    public function __construct(
        private PayrollCalculationService $calculator,
        private TimecardService $timecardService
    ) {}
}
```

### Policy Authorization

```php
public function viewAll(User $user): bool
{
    return $user->isAdmin() || $user->hasPermission('payroll.view-all');
}
```

### Livewire Component Layout

```php
class PayrollIndex extends Component
{
    use AuthorizesRequests;
    
    #[Layout('layouts.app')]
    #[Title('Payroll')]
    
    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
    }
}
```

### State Workflow Management

Use lifecycle services for state transitions:
```php
class PayrollLifecycleService
{
    public function process(PayRun $payRun): void
    {
        // Validate
        // Calculate
        // Update state
        // Trigger observers/events
    }
}
```

---

## 8. What Needs to Be Built (Sprint 2)

### Models

| Model | Purpose | Key Fields |
|-------|---------|-----------|
| **PayRate** | User's hourly/salary rate | `user_id`, `rate`, `rate_type`, `effective_date`, `is_active` |
| **PayRun** | Batch payroll period | `period_start`, `period_end`, `status`, `total_amount`, `processed_at` |
| **PayrollRecord** | Individual payroll entry | `payrun_id`, `user_id`, `timecard_id`, `gross_pay`, `deductions`, `net_pay` |

### Services

1. **PayrollCalculationService**
   - Calculate gross pay from timecard hours
   - Apply pay rate (base + overtime rules)
   - Calculate deductions (tax, benefits if applicable)

2. **PayrollProcessingService**
   - Validate timecards for a period
   - Generate payroll records
   - Calculate totals
   - Update pay run status

3. **PayrollReportService**
   - Generate payroll reports
   - Export data (CSV, PDF)
   - Summary statistics

### Permissions

```php
'payroll.view'          // View own payroll
'payroll.view-all'      // View all payroll
'payroll.create'        // Create pay rates
'payroll.process'       // Process payroll runs
'payroll.export'        // Export payroll
'payroll.approve'       // Approve payroll (if needed)
```

### Livewire Components

- Admin: PayrollProcessor, PayRateManager, PayrollReports
- User: PayrollHistory (view own payroll)

---

## 9. Reference Materials

### Available in Project-Manager (Older Version)

The `c:\project-manager` codebase has more mature implementations:
- **ProjectLaborCostService** - Complex labor calculations with burden rates
- **ProjectFinancialService** - Financial aggregations
- **PayRateType, UserPayRate, BurdenRate** models
- **Payroll Report UI** with overtime support
- **TimecardObserver** - Automatic labor cost updates
- **Financial fixtures** for testing

Use these as patterns but adapt to domain-driven architecture.

---

## 10. Quick Start: Sprint 2 Task Order

1. **Create PayRate Model** - Basic hourly rate per user
2. **Create PayRun Model** - Batch processing container
3. **Create PayrollRecord Model** - Individual payroll entries
4. **Create DB Migrations** - Schema for all three
5. **Create Payroll Domain** - ServiceProvider + infrastructure
6. **Define Permissions** - PayrollPermissions class
7. **Create PayrollPolicy** - Authorization rules
8. **Implement Services** - Calculation + Processing
9. **Create Livewire Components** - UI for reports/processing
10. **Write Tests** - Full test coverage for each component
11. **Add Routes** - web, admin, api, mobile
12. **Documentation** - Update architecture docs

---

## Useful Commands

```bash
# Generate a new domain component
php artisan make:livewire app.domains.payroll.livewire.admin.payroll-processor

# Create migrations
php artisan make:migration create_payroll_records_table

# Create models with all factories/tests
php artisan make:model Domains/Payroll/Models/PayrollRecord \
    --factory --migration --policy

# Run domain tests
php artisan test ./app/Domains/Payroll/Tests
```

---

## Key Files to Reference

| File | Purpose |
|------|---------|
| `app/Domains/Timecards/Providers/TimecardsServiceProvider.php` | Domain registration pattern |
| `app/Domains/Reports/Permissions/ReportPermissions.php` | Permission definitions |
| `app/Core/Auth/Permission/Contracts/PermissionRegistryContract.php` | Permission registry |
| `app/Domains/Timecards/Tests/Feature/TimecardsDomainScaffoldTest.php` | Test patterns |
| `tests/Pest.php` | Test configuration |
| `bootstrap/app.php` | Global middleware/service setup |

