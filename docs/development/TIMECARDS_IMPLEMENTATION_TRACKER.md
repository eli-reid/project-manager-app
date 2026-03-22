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
- [x] Milestone A: Foundation complete (domain wiring + core model/policy/permissions + baseline tests)
- [x] Milestone B: User lifecycle complete (index/create/edit/update/submit/reset)
- [ ] Milestone C: Admin lifecycle complete (list/filter/create/edit/approve/reject/bulk)
- [ ] Milestone D: Reporting and payroll compatibility complete
- [ ] Milestone E: Hardening and cutover complete

## Current Status Snapshot
- [x] Domain auto-registration is working via the shared domain provider.
- [x] Timecard and TimecardEntry models exist.
- [x] Timecards policy and permission map exist.
- [x] Timecards and timecard_entries migrations exist.
- [x] Factories exist for timecards and entries.
- [x] Baseline scaffold tests exist for admin index access.
- [x] Admin index route/component/view exists.
- [x] User web lifecycle exists.
- [x] Mobile lifecycle exists.
- [x] API endpoints exist.
- [x] Admin create/edit/show/transition flows exist.
- [ ] Reports/payroll/reminders exist.
- [ ] Observer/cache parity exists.

## Phase 1 - Domain Foundation (P0)
- [x] Add Timecards service provider and register admin routes/components
- [x] Register user web routes in Timecards service provider
- [x] Register mobile routes in Timecards service provider
- [x] Register API routes in Timecards service provider
- [x] Add Timecard and TimecardEntry domain models (new app)
- [x] Add Timecards permissions map and policy class
- [x] Expand policy abilities for reset, reports, and broader lifecycle parity
- [x] Add or align database migrations for timecards and entries
- [x] Add factories for timecard/timecard_entry test setup
- [x] Add baseline scaffold tests (guest redirect, unauthorized forbidden, authorized access)
- [ ] Add domain lifecycle services/actions for create, update, submit, approve, reject, reset, and bulk operations
- [ ] Add observer or domain-event hooks for total-hours sync and cache invalidation

## Phase 2 - User Lifecycle (P0)
- [x] Build user index Livewire page
- [x] Build user create flow with week normalization and duplicate-week prevention
- [x] Build shared responsive create UI instead of separate desktop/mobile controllers
- [x] Build user edit/update flow with draft-only lock enforcement
- [x] Build user detail page
- [x] Build submit flow with entry-required guard
- [x] Build rejected-to-draft reset flow
- [x] Build duplicate-week check endpoint/helper
- [x] Build entry add/update/delete orchestration inside domain actions/services
- [x] Add lifecycle feature tests for each state transition

## Phase 3 - Admin Lifecycle (P0/P1)
- [x] Build admin index page scaffold
- [x] Add admin index filters (status, employee, date range)
- [x] Build admin create/edit for any user
- [x] Build admin show page
- [x] Build admin approve/reject/reset status actions
- [ ] Build admin bulk actions (approve/reject/delete)
- [x] Add admin create/edit entry management flows
- [x] Add admin authorization and transition tests

## Phase 4 - Reports/Payroll Compatibility (P1)
- [ ] Implement approved-time reporting read models (user/project/date views)
- [ ] Implement payroll weekly/multi-week data builder contract
- [ ] Implement CSV/PDF export handlers or adapters
- [ ] Implement reminder/scheduler integration boundary
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
- 2026-03-22: Reconciled tracker with actual codebase status. Marked scaffolded foundation items complete and split remaining work into concrete lifecycle, admin, reporting, and hardening checklists.
- 2026-03-22: Completed user entry management and admin create/edit/delete flows with focused Timecards feature coverage.

## Next Slice (Execution Order)
1. Complete Phase 3 admin bulk approve/reject/delete actions.
2. Add remaining observer/cache invalidation hooks for downstream consumers.
3. Implement Phase 4 reports and payroll compatibility.
4. Add P0/P1 regression tests around reporting totals and admin bulk transitions.

## Concrete Build Order (Phase 1 + Phase 2)

### Slice 1 - Route and Provider Wiring
- [ ] Update `app/Domains/Timecards/Providers/TimecardsServiceProvider.php` to mount `Routes/web.php`, `Routes/mobile.php`, and `Routes/api.php` in addition to `Routes/admin.php`.
- [ ] Register user-facing Livewire components in `app/Domains/Timecards/Providers/TimecardsServiceProvider.php`.
- [ ] Implement user route skeleton in `app/Domains/Timecards/Routes/web.php`.
- [ ] Decide whether `app/Domains/Timecards/Routes/mobile.php` stays as alias routes to responsive Livewire pages or carries separate route names only.
- [ ] Implement API route skeleton in `app/Domains/Timecards/Routes/api.php` for duplicate-week checks.
- [ ] Extend `app/Domains/Timecards/Tests/Feature/TimecardsDomainScaffoldTest.php` to cover user web, mobile, and API route access.

### Slice 2 - Lifecycle Service Layer
- [ ] Add `app/Domains/Timecards/Services/TimecardWeekService.php` for week normalization, current/future week helpers, and duplicate-week detection.
- [ ] Add `app/Domains/Timecards/Services/TimecardEntrySyncService.php` for entry create/update/delete orchestration and total-hours recalculation.
- [ ] Add `app/Domains/Timecards/Services/TimecardLifecycleService.php` for create, update, submit, approve, reject, reset, and bulk transition rules.
- [ ] Update `app/Domains/Timecards/Policies/TimecardPolicy.php` to cover reset/report-related abilities once lifecycle services are in place.
- [ ] Keep Livewire components thin by moving week logic, duplicate detection, and transition rules into these services instead of component methods.

### Slice 3 - User Index and Detail
- [ ] Add `app/Domains/Timecards/Livewire/User/Timecards/Index.php`.
- [ ] Add `app/Domains/Timecards/Livewire/User/Timecards/Show.php`.
- [ ] Add `app/Domains/Timecards/Resources/Views/livewire/user/timecards/index.blade.php`.
- [ ] Add `app/Domains/Timecards/Resources/Views/livewire/user/timecards/show.blade.php`.
- [ ] Use `TimecardWeekService` to drive current-week and future-week creation UX from the index page.
- [ ] Add user access tests in `app/Domains/Timecards/Tests/Feature/` for own-timecard visibility and non-owner restrictions.

### Slice 4 - Shared Create and Edit Flow
- [ ] Add `app/Domains/Timecards/Livewire/User/Timecards/Form.php` for both create and edit states.
- [ ] Add `app/Domains/Timecards/Resources/Views/livewire/user/timecards/form.blade.php`.
- [ ] Use one responsive form instead of separate desktop/mobile controller flows.
- [ ] Drive create/edit behavior through `TimecardLifecycleService` and `TimecardEntrySyncService`.
- [ ] Enforce duplicate-week prevention through `TimecardWeekService`.
- [ ] Enforce draft-only editing and owner checks through policy plus lifecycle service guards.

### Slice 5 - Submit, Reset, and Duplicate Check Endpoint
- [ ] Add submit and reset actions to the user form/show flow using `TimecardLifecycleService`.
- [ ] Implement the duplicate-week endpoint in `app/Domains/Timecards/Routes/api.php`.
- [ ] Add a lightweight response path for week-exists checks that can support responsive UI validation.
- [ ] Add feature tests covering submit, rejected-to-draft reset, and duplicate-week prevention.

### Slice 6 - Observer and Hardening Hooks Needed Before Admin Expansion
- [ ] Add `app/Domains/Timecards/Observers/TimecardEntryObserver.php` or equivalent domain event wiring to keep `timecards.total_hours` in sync.
- [ ] Add `app/Domains/Timecards/Observers/TimecardObserver.php` or equivalent domain event wiring for cache invalidation and downstream recalculation hooks.
- [ ] Register observers in `app/Domains/Timecards/Providers/TimecardsServiceProvider.php`.
- [ ] Add focused observer tests before starting Phase 3 admin transitions.

## File Targets by Responsibility

### Provider and Routing
- [ ] `app/Domains/Timecards/Providers/TimecardsServiceProvider.php`
- [ ] `app/Domains/Timecards/Routes/web.php`
- [ ] `app/Domains/Timecards/Routes/mobile.php`
- [ ] `app/Domains/Timecards/Routes/api.php`
- [ ] `app/Domains/Timecards/Routes/admin.php`

### Services and Domain Logic
- [ ] `app/Domains/Timecards/Services/TimecardWeekService.php`
- [ ] `app/Domains/Timecards/Services/TimecardEntrySyncService.php`
- [ ] `app/Domains/Timecards/Services/TimecardLifecycleService.php`
- [ ] `app/Domains/Timecards/Policies/TimecardPolicy.php`

### User Livewire Surface
- [ ] `app/Domains/Timecards/Livewire/User/Timecards/Index.php`
- [ ] `app/Domains/Timecards/Livewire/User/Timecards/Form.php`
- [ ] `app/Domains/Timecards/Livewire/User/Timecards/Show.php`
- [ ] `app/Domains/Timecards/Resources/Views/livewire/user/timecards/index.blade.php`
- [ ] `app/Domains/Timecards/Resources/Views/livewire/user/timecards/form.blade.php`
- [ ] `app/Domains/Timecards/Resources/Views/livewire/user/timecards/show.blade.php`

### Observers and Events
- [ ] `app/Domains/Timecards/Observers/TimecardObserver.php`
- [ ] `app/Domains/Timecards/Observers/TimecardEntryObserver.php`

### Tests
- [ ] `app/Domains/Timecards/Tests/Feature/TimecardsDomainScaffoldTest.php`
- [ ] `app/Domains/Timecards/Tests/Feature/UserTimecardIndexTest.php`
- [ ] `app/Domains/Timecards/Tests/Feature/UserTimecardFormTest.php`
- [ ] `app/Domains/Timecards/Tests/Feature/UserTimecardLifecycleTest.php`
- [ ] `app/Domains/Timecards/Tests/Feature/TimecardDuplicateWeekApiTest.php`
- [ ] `app/Domains/Timecards/Tests/Feature/TimecardObserverTest.php`
