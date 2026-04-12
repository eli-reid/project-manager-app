# Payroll System Implementation Checklist

## Build Rules

- [ ] Keep User as identity and RBAC subject
- [ ] Keep Timecards as payroll source of truth for user-entered time
- [ ] Use Dailies only for manual reconciliation reporting
- [ ] Use ULIDs for all payroll domain entities
- [ ] Keep authorization policy-first and permission-driven
- [ ] Reuse core audit logs as primary event store

## Phase 1 - Data Foundation

### 1A - Payroll Core Tables
- [x] Create payroll employee profiles table
- [x] Create pay rate types table
- [x] Create pay rates table
- [ ] Create pay runs table
- [ ] Create payroll statements table
- [ ] Create deductions table
- [ ] Create employee deductions table

### 1B - Project and Timecard Extensions
- [ ] Add prevailing wage fields to projects
- [ ] Create cost codes table under Projects domain
- [ ] Add payroll fields to timecard entries (cost code, regular/OT/DT, prevailing fields)

### 1C - Constraints and Integrity
- [x] Ensure unique employee_number on payroll profiles
- [ ] Enforce one active employee rate per rate type and project scope
- [ ] Enforce payroll statement user/profile consistency in builder validation

## Phase 2 - Domain Registration and Settings

### 2A - Domain Provider
- [x] Add Payroll service provider
- [x] Register payroll settings config in SettingsRegistryContract
- [ ] Register payroll migrations from domain path when domain migrations are moved
- [ ] Register payroll permissions
- [ ] Register payroll routes
- [ ] Register payroll views and Livewire components

### 2B - Payroll Settings
- [x] Add reconciliation settings definitions
- [ ] Confirm settings sync into settings database in local/dev
- [ ] Add tests for settings registry integration

## Phase 3 - Models and Factories

### 3A - Models
- [x] PayrollEmployeeProfile model
- [x] PayRateType model
- [x] PayRate model
- [x] PayrollStatement model
- [ ] PayRun model
- [ ] Deduction model
- [ ] EmployeeDeduction model

### 3B - Relationships and Casts
- [x] Add encrypted cast for ssn_encrypted
- [x] Add typed rate relationships (profile -> rates -> rate type)
- [ ] Add PayRun and PayrollStatement full relationships

### 3C - Factories
- [x] PayrollEmployeeProfileFactory
- [x] PayRateTypeFactory (includes standard state)
- [x] PayRateFactory
- [x] PayrollStatementFactory baseline

## Phase 4 - Typed Rate Management

### 4A - Seeded System Types
- [ ] Seed standard pay rate type
- [ ] Seed prevailing_base pay rate type
- [ ] Seed prevailing_fringe pay rate type
- [ ] Prevent edits/deletes to protected system types

### 4B - Admin UI
- [ ] Build PayRateTypes admin index
- [ ] Build employee PayRates admin index
- [ ] Build employee PayRates form with type selection and project scope

## Phase 5 - Time to Payroll Pipeline

### 5A - Calculation Services
- [ ] Build PayrollRateResolutionService with typed precedence
- [ ] Build OvertimeCalculationService (weekly FLSA, CA daily, 7th-day)

### 5B - Timecard Integration
- [ ] Extend timecard forms with cost code selection
- [ ] Add validation rules V-01 through V-10 in payroll review pipeline
- [ ] Build admin payroll timecard review screen

### 5C - Dailies Reconciliation
- [ ] Build TimecardDailyReconciliation report (user/project/date/hours)
- [ ] Respect payroll.reconciliation settings in mismatch logic
- [ ] Keep require_cost_code_match default false until mapping exists

## Phase 6 - Pay Runs and Statements

### 6A - Services
- [ ] Build PayRunService orchestration
- [ ] Build GrossToNetService
- [ ] Build TaxWithholdingService with configurable tables
- [ ] Build PayrollStatementBuilderService

### 6B - Workflow
- [ ] Create preview pay run flow
- [ ] Require Controller approval before finalize
- [ ] Lock finalized runs and statements against in-place mutation

### 6C - UI
- [ ] Build PayRuns admin index
- [ ] Build PayRuns create screen
- [ ] Build PayRuns show screen with approve/finalize/void actions

## Phase 7 - Employee Payroll Experience

- [ ] Build PayStubs user index
- [ ] Build PayStubs user show page
- [ ] Add PDF pay stub generation

## Phase 8 - Reporting and Compliance

- [ ] Build certified payroll report generation (WH-347)
- [ ] Build labor cost report by project/cost code/employee
- [ ] Build union remittance report
- [ ] Register payroll reports in report registry

## Phase 9 - Audit and Monitoring

- [ ] Log payroll mutations with core AuditLogger
- [ ] Add payroll hash-chain digest layer
- [ ] Build payroll audit report screen
- [ ] Add nightly digest validation task and alerts

## Phase 10 - Notifications

- [ ] Register payroll notification definitions (TC/PR/EM/SY/CO)
- [ ] Wire notifications to payroll lifecycle events
- [ ] Add notification tests for critical events

## Phase 11 - Forecasting

- [ ] Build forecasting service (trailing, project-based, headcount)
- [ ] Build forecasting widgets
- [ ] Build forecasting reports

## Phase 12 - Integrations (Deferred)

- [ ] Keep all third-party payroll integrations deferred until core payroll stabilizes
- [ ] Revisit accounting export first when integration phase starts
- [ ] Add retry, DLQ, and health monitoring framework before enabling endpoints

## Verification Checklist

- [x] Typed rate test passes (standard type with different employee values)
- [x] SSN encrypted cast test passes
- [x] Reconciliation settings keys test passes
- [ ] Run full payroll test group after Phase 1 completion
- [ ] Run migration test in clean database state
- [ ] Validate payroll settings visibility in admin settings editor
