# Appendix — Payroll Domain

## 1. Overview

This appendix defines the payroll domain model — all core entities, their attributes, relationships, validation rules, and lifecycle states. It serves as the single source of truth for developers, DBAs, and business analysts working on the payroll system.

## 2. Entity Definitions

### 2.1 Employee

The central entity representing a payroll-eligible worker.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `employee_id` | UUID | Yes | Primary key. |
| `employee_number` | VARCHAR(20) | Yes | Human-readable identifier (e.g., EMR-0042). |
| `first_name` | VARCHAR(50) | Yes | Legal first name. |
| `last_name` | VARCHAR(50) | Yes | Legal last name. |
| `ssn_encrypted` | VARBINARY | Yes | SSN, AES-256 encrypted at rest. |
| `date_of_birth` | DATE | Yes | Used for tax calculations and compliance. |
| `hire_date` | DATE | Yes | Original hire date. |
| `termination_date` | DATE | No | Null if currently active. |
| `status` | ENUM | Yes | `active`, `inactive`, `terminated`, `on_leave`. |
| `pay_type` | ENUM | Yes | `hourly`, `salary`, `piece_rate`. |
| `default_rate` | DECIMAL(10,4) | Yes | Base hourly rate or salary equivalent. |
| `department` | VARCHAR(50) | No | Organizational department. |
| `job_classification` | VARCHAR(50) | Yes | Trade/role (e.g., Journeyman Electrician). |
| `union_code` | VARCHAR(20) | No | Union affiliation code, if applicable. |
| `direct_deposit_active` | BOOLEAN | Yes | Whether ACH is enabled. |
| `created_at` | TIMESTAMP | Yes | Record creation timestamp. |
| `updated_at` | TIMESTAMP | Yes | Last modification timestamp. |

**Business Rules**
- `employee_number` must be unique and immutable once assigned.
- `ssn_encrypted` is write-only for payroll admins; read access requires dual authorization.
- `default_rate` can be overridden at the project level for prevailing-wage jobs.
- An employee with `status = terminated` cannot have new timecards submitted.

### 2.2 PayRate

Tracks rate history and project-specific overrides.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `rate_id` | UUID | Yes | Primary key. |
| `employee_id` | UUID | Yes | FK → Employee. |
| `project_id` | UUID | No | FK → Project. Null = default rate. |
| `rate_type` | ENUM | Yes | `base`, `prevailing_base`, `prevailing_fringe`. |
| `rate_amount` | DECIMAL(10,4) | Yes | Dollar amount per hour. |
| `effective_date` | DATE | Yes | Start of rate validity. |
| `expiration_date` | DATE | No | End of rate validity. Null = current. |
| `approved_by` | UUID | Yes | FK → User who authorized the rate. |

**Business Rules**
- Only one active rate per `rate_type` per `project_id` per employee at any time.
- Rate changes require approval from Payroll Admin or Controller.
- Historical rates are never deleted — they expire and new records are created.

### 2.3 Timecard

Daily work record linking an employee to a project. *Note: Full subsystem details in Appendix — Timecards.*

| Attribute | Type | Required | Description |
|---|---|---|---|
| `timecard_id` | UUID | Yes | Primary key. |
| `employee_id` | UUID | Yes | FK → Employee. |
| `project_id` | UUID | Yes | FK → Project. |
| `cost_code_id` | UUID | Yes | FK → CostCode. |
| `work_date` | DATE | Yes | Date of work performed. |
| `regular_hours` | DECIMAL(5,2) | Yes | Straight-time hours. |
| `overtime_hours` | DECIMAL(5,2) | No | OT hours (1.5×). Default 0. |
| `double_time_hours` | DECIMAL(5,2) | No | DT hours (2.0×). Default 0. |
| `status` | ENUM | Yes | `draft`, `submitted`, `approved`, `rejected`, `processed`. |
| `submitted_by` | UUID | Yes | FK → User (employee or foreman). |
| `approved_by` | UUID | No | FK → User (foreman). |
| `notes` | TEXT | No | Free-text field for exceptions or comments. |
| `created_at` | TIMESTAMP | Yes | Record creation. |
| `updated_at` | TIMESTAMP | Yes | Last modification. |

### 2.4 Project

Represents a job site or contract.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `project_id` | UUID | Yes | Primary key. |
| `project_number` | VARCHAR(20) | Yes | Human-readable job number. |
| `project_name` | VARCHAR(100) | Yes | Descriptive name. |
| `client_name` | VARCHAR(100) | Yes | Customer/owner. |
| `address` | TEXT | No | Physical job site address. |
| `is_prevailing_wage` | BOOLEAN | Yes | Whether prevailing-wage rules apply. |
| `wage_determination_id` | VARCHAR(30) | No | DOL wage determination number. |
| `start_date` | DATE | Yes | Project start. |
| `end_date` | DATE | No | Projected completion. |
| `status` | ENUM | Yes | `active`, `completed`, `on_hold`, `cancelled`. |
| `budget_labor_hours` | DECIMAL(10,2) | No | Budgeted total labor hours. |
| `budget_labor_cost` | DECIMAL(12,2) | No | Budgeted total labor dollars. |

**Business Rules**
- `is_prevailing_wage = true` requires a valid `wage_determination_id`.
- Timecards cannot be submitted against a project with `status ≠ active`.
- Changing a project to `completed` triggers a final certified payroll generation.

### 2.5 CostCode

Subdivision of a project for labor-cost tracking.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `cost_code_id` | UUID | Yes | Primary key. |
| `project_id` | UUID | Yes | FK → Project. |
| `code` | VARCHAR(20) | Yes | Code identifier (e.g., 16010). |
| `description` | VARCHAR(100) | Yes | Work category (e.g., Rough-In Wiring). |
| `budget_hours` | DECIMAL(10,2) | No | Allocated hours. |
| `budget_cost` | DECIMAL(12,2) | No | Allocated labor dollars. |

### 2.6 PayRun

A payroll processing batch.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `pay_run_id` | UUID | Yes | Primary key. |
| `pay_period_start` | DATE | Yes | First day of pay period. |
| `pay_period_end` | DATE | Yes | Last day of pay period. |
| `pay_date` | DATE | Yes | Date funds are disbursed. |
| `status` | ENUM | Yes | `draft`, `preview`, `approved`, `finalized`, `void`. |
| `total_gross` | DECIMAL(14,2) | No | Sum of all gross pay in the run. |
| `total_net` | DECIMAL(14,2) | No | Sum of all net pay in the run. |
| `total_taxes` | DECIMAL(14,2) | No | Sum of all tax withholdings. |
| `employee_count` | INT | No | Number of employees in the run. |
| `created_by` | UUID | Yes | FK → User. |
| `approved_by` | UUID | No | FK → User (Controller). |
| `finalized_at` | TIMESTAMP | No | When the run was locked. |

**Business Rules**
- A pay run cannot be finalized without Controller approval.
- Once `status = finalized`, no modifications are permitted — corrections require a supplemental pay run.
- Voiding a finalized run requires System Admin authorization and creates a reversal record.

### 2.7 PayStub

Computed pay record per employee per pay run.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `pay_stub_id` | UUID | Yes | Primary key. |
| `pay_run_id` | UUID | Yes | FK → PayRun. |
| `employee_id` | UUID | Yes | FK → Employee. |
| `total_regular_hours` | DECIMAL(7,2) | Yes | Sum of regular hours. |
| `total_ot_hours` | DECIMAL(7,2) | Yes | Sum of overtime hours. |
| `total_dt_hours` | DECIMAL(7,2) | Yes | Sum of double-time hours. |
| `gross_pay` | DECIMAL(12,2) | Yes | Total earnings. |
| `federal_tax` | DECIMAL(10,2) | Yes | Federal income tax withheld. |
| `state_tax` | DECIMAL(10,2) | Yes | State income tax withheld. |
| `local_tax` | DECIMAL(10,2) | No | Local/city tax withheld. |
| `social_security` | DECIMAL(10,2) | Yes | Employee SS contribution. |
| `medicare` | DECIMAL(10,2) | Yes | Employee Medicare contribution. |
| `other_deductions` | DECIMAL(10,2) | No | Sum of voluntary deductions. |
| `net_pay` | DECIMAL(12,2) | Yes | Take-home amount. |
| `ytd_gross` | DECIMAL(14,2) | Yes | Year-to-date gross earnings. |
| `ytd_federal_tax` | DECIMAL(12,2) | Yes | Year-to-date federal tax. |
| `ytd_net` | DECIMAL(14,2) | Yes | Year-to-date net pay. |

### 2.8 Deduction

Defines a deduction type and its configuration.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `deduction_id` | UUID | Yes | Primary key. |
| `name` | VARCHAR(50) | Yes | Deduction name (e.g., Union Dues IBEW 223). |
| `category` | ENUM | Yes | `tax`, `garnishment`, `voluntary`, `union`, `benefit`. |
| `calculation_method` | ENUM | Yes | `flat`, `percentage`, `hourly`, `per_period`. |
| `amount` | DECIMAL(10,4) | Yes | Dollar amount or percentage. |
| `priority` | INT | Yes | Processing order (lower = first). |
| `pre_tax` | BOOLEAN | Yes | Whether deducted before tax calculation. |
| `max_annual` | DECIMAL(10,2) | No | Annual cap, if applicable. |

### 2.9 EmployeeDeduction

Links a deduction to a specific employee.

| Attribute | Type | Required | Description |
|---|---|---|---|
| `employee_deduction_id` | UUID | Yes | Primary key. |
| `employee_id` | UUID | Yes | FK → Employee. |
| `deduction_id` | UUID | Yes | FK → Deduction. |
| `override_amount` | DECIMAL(10,4) | No | Employee-specific amount override. |
| `effective_date` | DATE | Yes | Start of deduction. |
| `end_date` | DATE | No | End of deduction. Null = ongoing. |
| `status` | ENUM | Yes | `active`, `suspended`, `ended`. |

## 3. Entity Relationship Diagram

```text
┌───────────┐        ┌───────────┐        ┌───────────┐
│ Employee  │──1:*──│ PayRate    │        │ Project    │
│           │       └───────────┘        │           │
│           │──1:*──┌───────────┐──*:1──│           │
│           │       │ Timecard   │       │           │──1:*──┌───────────┐
│           │       └───────────┘       │           │       │ CostCode   │
└───────────┘                            └───────────┘       └───────────┘
│           │──1:*──┌───────────┐──*:1──┌───────────┐
│           │       │ PayStub    │      │ PayRun     │
│           │       └───────────┘      └───────────┘
│           │
│           │──1:*──┌──────────────────┐──*:1──┌───────────┐
│           │       │ EmployeeDeduction│       │ Deduction  │
└───────────┘       └──────────────────┘       └───────────┘
````

## 4. Domain Rules

### 4.1 Rate Resolution

When calculating pay, the system resolves the applicable rate using this precedence:

1.  Project-specific prevailing rate (if project `is_prevailing_wage = true`).
2.  Employee's current default rate (most recent active PayRate with `project_id = null`).
3.  Job classification default (fallback if no employee-specific rate exists).

### 4.2 Pay Calculation Formula

```text
Regular Pay     = regular_hours × applicable_base_rate
OT Pay          = overtime_hours × applicable_base_rate × 1.5
DT Pay          = double_time_hours × applicable_base_rate × 2.0
Fringe Pay      = total_hours × fringe_rate  (prevailing wage only)

Gross Pay       = Regular Pay + OT Pay + DT Pay + Fringe Pay
Pre-Tax Deductions = Σ (active pre-tax deductions)
Taxable Gross   = Gross Pay − Pre-Tax Deductions
Tax Withholdings= f(Taxable Gross, W-4 elections, state tables)
Post-Tax Deductions = Σ (active post-tax deductions)
Net Pay         = Gross Pay − Pre-Tax Deductions − Tax Withholdings − Post-Tax Deductions
```

### 4.3 State-Specific Rules

The system must support jurisdiction-specific variations:

| State         | Rule                                                                            |
| ------------- | ------------------------------------------------------------------------------- |
| Massachusetts | No daily OT; weekly OT at 40 hrs; Sunday premium for retail (N/A construction). |
| California    | Daily OT > 8 hrs; daily DT > 12 hrs; 7th consecutive day premium.               |
| Florida       | No state income tax; federal rules only.                                        |
| New York      | Weekly OT at 40 hrs; prevailing-wage supplements tracked separately.            |

### 4.4 Status Lifecycles

**Employee Status**

```text
active → on_leave → active
active → inactive → active
active → terminated (terminal)
```

**Timecard Status**

```text
draft → submitted → approved → processed (terminal)
draft → submitted → rejected → draft (cycle)
```

**PayRun Status**

```text
draft → preview → approved → finalized (terminal)
finalized → void (requires System Admin; terminal)
```

## 5. Data Integrity Constraints

| Constraint   | Rule                                                           |
| ------------ | -------------------------------------------------------------- |
| Referential  | All foreign keys enforced with `ON DELETE RESTRICT`.           |
| Temporal     | `effective_date < expiration_date` on all date-ranged records. |
| Financial    | All monetary fields use `DECIMAL` — never floating point.      |
| Uniqueness   | One active rate per type per project per employee.             |
| Immutability | Finalized PayRuns and processed Timecards are read-only.       |
| Encryption   | SSN and bank account fields encrypted at rest (AES-256).       |

## 6. Enumeration Definitions

### 6.1 Pay Types

| Value        | Description                      |
| ------------ | -------------------------------- |
| `hourly`     | Paid per hour worked.            |
| `salary`     | Fixed amount per pay period.     |
| `piece_rate` | Paid per unit of work completed. |

### 6.2 Rate Types

| Value               | Description                                       |
| ------------------- | ------------------------------------------------- |
| `base`              | Standard hourly rate.                             |
| `prevailing_base`   | Government-mandated base rate for public works.   |
| `prevailing_fringe` | Government-mandated fringe rate for public works. |

### 6.3 Deduction Categories

| Value         | Description                               |
| ------------- | ----------------------------------------- |
| `tax`         | Government-mandated tax withholdings.     |
| `garnishment` | Court-ordered deductions.                 |
| `voluntary`   | Employee-elected deductions.              |
| `union`       | Union dues and assessments.               |
| `benefit`     | Employer-sponsored benefit contributions. |

**End of Appendix — Payroll Domain**
