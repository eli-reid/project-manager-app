# Timecards Implementation Tracker

## Purpose
Execution tracker for migrating Timecards from prototype implementation to the new project-manager-app domain architecture and UI style.

## Working Rules
- Use policy-first authorization checks.
- Keep lifecycle transitions in dedicated actions/services.
- Keep Livewire components thin; move business logic into domain services/actions.
- Treat desktop/mobile as one responsive flow unless platform differences are required.
- Every completed item must have corresponding Pest coverage.

## Milestones
- [ ] Milestone A: Foundation complete (domain wiring + core model/policy/permissions + baseline tests)
- [ ] Milestone B: User lifecycle complete (index/create/edit/update/submit/reset)
- [ ] Milestone C: Admin lifecycle complete (list/filter/create/edit/approve/reject/bulk)
- [ ] Milestone D: Reporting and payroll compatibility complete
- [ ] Milestone E: Hardening and cutover complete

## Phase 1 - Domain Foundation (P0)
- [ ] Add Timecards service provider and register domain routes/components
- [ ] Add Timecard and TimecardEntry domain models (new app)
- [ ] Add Timecards permissions map and policy class
- [ ] Add or align database migrations for timecards and entries
- [ ] Add factories for timecard/timecard_entry test setup
- [ ] Add baseline scaffold tests (guest redirect, unauthorized forbidden, authorized access)

## Phase 2 - User Lifecycle (P0)
- [ ] Build user index Livewire page
- [ ] Build user create flow with week normalization and duplicate-week prevention
- [ ] Build user edit/update flow with draft-only lock enforcement
- [ ] Build submit flow with entry-required guard
- [ ] Build rejected-to-draft reset flow
- [ ] Add lifecycle feature tests for each state transition

## Phase 3 - Admin Lifecycle (P0/P1)
- [ ] Build admin index/filter page
- [ ] Build admin create/edit for any user
- [ ] Build admin approve/reject/reset status actions
- [ ] Build admin bulk actions (approve/reject/delete)
- [ ] Add admin authorization and transition tests

## Phase 4 - Reports/Payroll Compatibility (P1)
- [ ] Implement approved-time reporting read models (user/project/date views)
- [ ] Implement payroll weekly/multi-week data builder contract
- [ ] Implement CSV/PDF export handlers or adapters
- [ ] Add regression tests verifying report totals and row grouping

## Phase 5 - Cleanup and Hardening (P0/P1)
- [ ] Remove duplicated desktop/mobile action logic
- [ ] Replace inline validation with dedicated request/rule objects
- [ ] Add observer tests for total-hours sync and cache invalidation
- [ ] Add query performance assertions for list/detail/report hotspots
- [ ] Verify compatibility for downstream financial/report consumers

## Backlog - Deferred/Optional (P2)
- [ ] Reminder/notification UX enhancements
- [ ] Copy-from-previous-week utility
- [ ] Bulk edit utilities for repeated entries

## Risks and Mitigations
- [ ] Risk: Lifecycle rules diverge across actions
  Mitigation: Use a single transition service/state ruleset.
- [ ] Risk: Reintroducing N+1 query behavior
  Mitigation: Add query-focused tests and preloading policies.
- [ ] Risk: Authorization regressions for non-admin users
  Mitigation: Add explicit non-admin permission-path feature tests.
- [ ] Risk: Legacy behavior hidden in controller branches
  Mitigation: Keep parity matrix updated before each implementation slice.

## Progress Log
- 2026-03-20: Created parity matrix and implementation tracker documents.

## Next Slice (Execution Order)
1. Complete Phase 1 foundation tasks.
2. Implement Phase 2 user lifecycle flows.
3. Add P0 tests before Phase 3 admin rollout.
