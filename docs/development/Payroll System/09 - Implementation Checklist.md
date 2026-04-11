# Payroll System Implementation Checklist

## Goal

Rebuild the payroll domain from the approved specification while aligning with this application's existing architecture, conventions, and permission system.

## Guiding Decisions

- [ ] Treat the current payroll domain as disposable scaffold code.
- [ ] Keep `User` as the system identity and RBAC subject.
- [ ] Introduce payroll-specific profile and financial models around `User` instead of replacing it with a separate authentication entity.
- [ ] Use ULIDs for new payroll entities to match app conventions.
- [ ] Keep authorization policy-first and permission-driven.
- [ ] Rebuild payroll in phases instead of implementing every appendix at once.
- [ ] Defer external banking, tax filing, and government integrations until the internal payroll ledger is stable.

## Phase 0 - Architecture Decisions

- [ ] Confirm payroll will be modeled as `User` + payroll-owned profile/data tables.
- [ ] Confirm the canonical status lifecycle for payroll periods, runs, statements, corrections, and exports.
- [ ] Confirm the first release scope.
- [ ] Decide which features are core for v1.
- [ ] Core recommendation: periods, runs, statements, rates, deductions, preview/finalize workflow, payroll history, reporting foundation.
- [ ] Later recommendation: ACH, tax portal filing, certified payroll submission, union remittance, forecasting, advanced audit chain features.
- [ ] Confirm naming conventions for new models, services, permissions, and route groups.
- [ ] Confirm sensitive-data handling boundaries for bank details, tax data, and SSN-related fields.

## Phase 1 - Remove Existing Payroll Scaffold

- [ ] Inventory all current payroll references across the repo.
- [ ] Remove the current payroll provider registration from `bootstrap/providers.php`.
- [ ] Remove the current payroll domain folder under `app/Domains/Payroll`.
- [ ] Remove or replace payroll migrations tied to the disposable scaffold.
- [ ] Remove payroll Livewire components and views that depend on the old schema.
- [ ] Remove payroll tests that validate the old scaffold behavior.
- [ ] Remove or refactor cross-domain references to old payroll models and services.
- [ ] Update any permission tests that still assert old payroll permission names.
- [ ] Clear compiled/bootstrap caches after teardown.

## Phase 2 - Define New Payroll Domain Model

- [ ] Create a payroll domain map that matches the spec and the app's architecture.
- [ ] Define the new core entities.
- [ ] `PayrollEmployeeProfile`
- [ ] `PayrollPeriod`
- [ ] `PayrollRun`
- [ ] `PayrollStatement`
- [ ] `PayrollStatementLine`
- [ ] `PayrollPayRate`
- [ ] `PayrollPayRateType` if still needed after redesign
- [ ] `PayrollDeductionDefinition`
- [ ] `PayrollEmployeeDeduction`
- [ ] `PayrollDirectDepositAccount`
- [ ] `PayrollTaxProfile` or equivalent elections model
- [ ] `PayrollCorrection`
- [ ] `CertifiedPayrollReport` or export aggregate if included in early scope
- [ ] Define ownership and relationships for each entity.
- [ ] Document which entities are immutable after finalization.
- [ ] Define which data belongs in the general audit subsystem versus payroll-specific audit records.

## Phase 3 - Database Design

- [ ] Write the new schema design before building migrations.
- [ ] Define table names, foreign keys, unique constraints, and effective-date rules.
- [ ] Use DECIMAL for all financial values.
- [ ] Add indexes for pay period, run, employee, project, and status-heavy queries.
- [ ] Add uniqueness constraints for active rate windows where applicable.
- [ ] Add encryption/casting strategy for sensitive payroll fields.
- [ ] Decide how to store masked versus encrypted values.
- [ ] Plan archival and soft-delete behavior explicitly.
- [ ] Ensure migrations follow project conventions and are safe for fresh installs.

## Phase 4 - Permission and Authorization Design

- [ ] Replace the scaffold payroll permissions with a spec-aligned permission map.
- [ ] Define granular permissions for own-data versus all-data access.
- [ ] Suggested categories:
- [ ] `payroll.view-own`
- [ ] `payroll.view-all`
- [ ] `payroll.rates.manage`
- [ ] `payroll.periods.manage`
- [ ] `payroll.runs.preview`
- [ ] `payroll.runs.approve`
- [ ] `payroll.runs.finalize`
- [ ] `payroll.runs.void`
- [ ] `payroll.statements.export`
- [ ] `payroll.corrections.manage`
- [ ] `payroll.reports.certified-payroll`
- [ ] `payroll.reports.tax`
- [ ] `payroll.audit.view`
- [ ] Map spec roles into the existing role system through permissions, not hardcoded role names.
- [ ] Build policies around these permissions.
- [ ] Decide whether admin bypass remains allowed or payroll follows stricter separation-of-duties rules.
- [ ] Add tests for policy behavior using role/permission assignments.

## Phase 5 - Timecard to Payroll Pipeline

- [ ] Define the exact handoff from approved timecards into payroll calculation.
- [ ] Reuse the existing Timecards domain instead of duplicating time entry concepts.
- [ ] Define which timecard fields are required for payroll calculation.
- [ ] Add prevailing-wage support where project/timecard data requires it.
- [ ] Implement rate resolution order.
- [ ] Project-specific prevailing wage rate
- [ ] Employee payroll rate
- [ ] Fallback/default classification rule if needed
- [ ] Define overtime calculation rules by jurisdiction.
- [ ] Define adjustment and reversal mechanics for processed time.
- [ ] Ensure processed time cannot be silently recalculated without traceability.

## Phase 6 - Payroll Calculation Engine

- [ ] Implement a service layer for payroll calculation that is deterministic and testable.
- [ ] Separate responsibilities into focused services.
- [ ] Rate resolution
- [ ] Hours classification
- [ ] Gross pay calculation
- [ ] Deduction resolution
- [ ] Tax calculation placeholder or adapter boundary
- [ ] Net pay calculation
- [ ] Statement generation
- [ ] Define preview run generation behavior.
- [ ] Define approval/finalization rules.
- [ ] Lock finalized data against mutation.
- [ ] Implement correction workflows through explicit follow-up records, not in-place edits.

## Phase 7 - UI and Workflow

- [ ] Build new payroll admin workflows with Livewire and Flux components.
- [ ] Build payroll period management UI.
- [ ] Build preview pay run UI.
- [ ] Build payroll statement drill-down UI.
- [ ] Build correction workflow UI.
- [ ] Build employee payroll history UI.
- [ ] Add approval/finalization confirmations with clear state transitions.
- [ ] Add exception states for missing rates, invalid time, unapproved entries, and locked periods.
- [ ] Ensure mobile and desktop experiences follow existing app patterns where relevant.

## Phase 8 - Reporting Foundation

- [ ] Implement payroll summary reporting based on finalized statements.
- [ ] Add project labor cost rollups that use the new payroll source of truth.
- [ ] Define export formats for statements and summaries.
- [ ] Add certified payroll reporting only after the required prevailing-wage data model exists.
- [ ] Add tax-report outputs only after tax data is reliable and versioned.

## Phase 9 - Sensitive Data and Compliance Hardening

- [ ] Define how SSN-related data is stored, masked, and accessed.
- [ ] Define how direct-deposit details are encrypted and rotated.
- [ ] Add access logging for sensitive payroll data views.
- [ ] Add audit coverage for payroll mutations, approvals, finalization, voids, and exports.
- [ ] Decide whether immutable hash-chain audit requirements are in initial scope or deferred.
- [ ] Define legal-hold and retention expectations before implementing archival behavior.

## Phase 10 - Integrations

- [ ] Design integration boundaries as adapters, not embedded business logic.
- [ ] Add accounting export first if external integration is needed early.
- [ ] Defer ACH file generation until direct-deposit and finalization flows are stable.
- [ ] Defer tax filing integrations until tax calculations and filing requirements are confirmed.
- [ ] Defer government portal integrations until certified payroll exports are verified.
- [ ] Add retry, DLQ, monitoring, and credential management as part of integration work.

## Phase 11 - Testing Strategy

- [ ] Write tests alongside each phase instead of backfilling at the end.
- [ ] Add model tests for rate windows, status transitions, and constraints.
- [ ] Add service tests for payroll calculation scenarios.
- [ ] Add feature tests for permissions and workflows.
- [ ] Add Livewire tests for payroll admin components.
- [ ] Add scenario tests for corrections, approvals, and finalization locking.
- [ ] Add regression tests for project labor cost reporting that depends on payroll outputs.
- [ ] Run the minimum targeted Pest coverage for each increment.

## Phase 12 - Cutover and Cleanup

- [ ] Remove any remaining dead references to scaffold payroll classes.
- [ ] Verify bootstrap/provider registration only loads the new payroll domain.
- [ ] Clear caches and confirm routes, views, and Livewire components register correctly.
- [ ] Verify permission synchronization includes the new payroll capabilities.
- [ ] Run targeted tests for Payroll, Projects, Reports, Timecards, and permission sync.
- [ ] Document any deferred spec items explicitly so they do not get mistaken for completed work.

## Suggested Build Sequence

- [ ] Step 1: Teardown existing payroll scaffold.
- [ ] Step 2: Finalize new payroll architecture decisions.
- [ ] Step 3: Build schema and models.
- [ ] Step 4: Build permissions and policies.
- [ ] Step 5: Build timecard-to-payroll calculation pipeline.
- [ ] Step 6: Build payroll periods, runs, and statements.
- [ ] Step 7: Build admin workflow UI.
- [ ] Step 8: Build employee payroll history.
- [ ] Step 9: Build reporting outputs.
- [ ] Step 10: Add compliance hardening.
- [ ] Step 11: Add integrations.
- [ ] Step 12: Final cleanup and regression validation.

## Explicit Non-Goals for the First Build Pass

- [ ] Do not mirror the spec literally where it conflicts with app-wide conventions.
- [ ] Do not replace `User` as the authentication identity.
- [ ] Do not implement banking and tax integrations before the internal payroll model is stable.
- [ ] Do not add speculative compliance features without a concrete technical design.
- [ ] Do not leave old scaffold classes in place once the rebuild starts.