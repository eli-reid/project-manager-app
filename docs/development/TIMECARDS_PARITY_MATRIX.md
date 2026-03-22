# Timecards Domain Parity Matrix

## Purpose
Track feature parity between the legacy implementation in project-manager and the target implementation in project-manager-app.

## Source References
- Legacy user controller: project-manager/app/Http/Controllers/TimecardController.php
- Legacy admin controller: project-manager/app/Http/Controllers/Admin/TimecardController.php
- Legacy user routes: project-manager/routes/web/timecards.php
- Legacy admin routes: project-manager/routes/admin/timecards.php
- Legacy model lifecycle: project-manager/app/Models/Timecard.php
- Legacy policy baseline: project-manager/app/Policies/TimecardPolicy.php
- New domain scaffold: app/Domains/Timecards

## Current New-App Baseline
- [x] Admin index route exists.
- [x] Admin index Livewire component exists.
- [x] Admin index Blade view exists.
- [x] Timecard policy exists.
- [x] Permission definitions exist.
- [x] Domain models exist.
- [x] Factories exist.
- [x] Migrations exist.
- [x] Baseline admin access tests exist.
- [x] User routes exist.
- [x] Mobile routes exist.
- [x] API routes exist.

## Parity Checklist

### User Flow (P0/P1)
- [x] User list parity
	Legacy surface: `TimecardController@index`
	Legacy behavior: own timecards list, totals, current/future week UX.
	Target: Livewire user index in Timecards domain.
	Cleanup: move query/date logic to a query action/service.

- [x] User create parity
	Legacy surface: `create + mobileCreate`
	Legacy behavior: week normalization and duplicate-week check.
	Target: shared responsive Livewire create flow.
	Cleanup: remove desktop/mobile duplication.

- [x] User store parity
	Legacy surface: `store + mobileStore`
	Legacy behavior: two payload shapes and duplicated logic.
	Target: single create action with normalized request contract.
	Cleanup: replace inline validation with dedicated rules/request objects.

- [x] User edit parity
	Legacy surface: `edit + mobileEdit`
	Legacy behavior: draft-only edits and owner checks.
	Target: shared edit component with lock guards.
	Cleanup: centralize lock rules in a lifecycle action.

- [x] User update parity
	Legacy surface: `update`
	Legacy behavior: entry sync/upsert/delete inside controller.
	Target: `UpdateTimecard` action plus entry sync service.
	Cleanup: remove nested orchestration from UI/controller code.

- [x] User submit parity
	Legacy surface: `submit + mobileSubmit`
	Legacy behavior: draft-only submit, entries required, notifications.
	Target: submit action with transition policy.
	Cleanup: keep transitions in one lifecycle service.

- [x] User reset parity
	Legacy surface: `resetToDraft + mobileResetToDraft`
	Legacy behavior: rejected-only reset to draft.
	Target: reset action.
	Cleanup: centralize transition checks.

- [x] User detail parity
	Legacy surface: `show + mobileShow`
	Legacy behavior: owner/permission checks with entry loading.
	Target: show component with policy-first auth.
	Cleanup: remove inline permission strings.

- [x] Duplicate check API parity
	Legacy surface: `checkExisting`
	Legacy behavior: week-exists lookup endpoint.
	Target: read endpoint in Timecards API routes.
	Cleanup: reuse normalized week key helper.

### Admin Flow (P0/P1)
- [x] Admin list parity
	Legacy surface: `Admin\TimecardController@index`
	Legacy behavior: filtered admin listing.
	Target: Livewire admin index/filter page.
	Cleanup: move filtering to query object.
	Current new-app status: filters and review/edit links exist.

- [x] Admin create/edit parity
	Legacy surface: `create/store/edit/update`
	Legacy behavior: full user/timecard/entries management.
	Target: admin form plus create/update actions.
	Cleanup: separate status transitions from entry edits.

- [x] Admin transitions parity
	Legacy surface: `approve/reject/resetStatus`
	Legacy behavior: manual status transitions.
	Target: lifecycle actions.
	Cleanup: use one transition ruleset.

- [ ] Admin bulk parity
	Legacy surface: `bulkAction`
	Legacy behavior: approve/reject/delete loops.
	Target: bulk action service/actions.
	Cleanup: transaction-safe batching and audit hook.

### Reporting and Operations (P1/P2)
- [ ] Admin reports parity
	Legacy surface: `reports + grouping helpers`
	Legacy behavior: approved-entry reports by user/project/date.
	Target: reporting read models with stable API.
	Cleanup: keep reporting queries out of write path.

- [ ] Payroll/export parity
	Legacy surface: `payrollReport/export CSV/PDF`
	Legacy behavior: multi-week payroll rollups.
	Target: shared report builder in a reporting boundary.
	Cleanup: formal output contract for downstream use.

- [ ] Reminders parity
	Legacy surface: `sendReminders`
	Legacy behavior: manual reminder trigger.
	Target: Scheduler/Core integration boundary.
	Cleanup: remove reminder orchestration from UI flow.

### Domain Behavior and Hardening (P0)
- [ ] Observers/cache parity
	Legacy surface: `TimecardObserver + TimecardEntryObserver`
	Legacy behavior: update totals, labor costs, and clear caches.
	Target: domain events/observers in Timecards.
	Cleanup: add deterministic cache key strategy and tests.

## Known Gaps to Resolve Early
- UpdateTimecardRequest in legacy is not usable as a migration source (authorize false, empty rules).
- Integration tests in legacy for lifecycle/mobile are placeholders only.
- Admin bulk actions, reports, payroll exports, and reminder flows still need to be ported.

## Definition of Parity Complete
- Every P0 row implemented and tested in project-manager-app.
- P1 rows implemented or explicitly deferred with signed-off rationale.
- No undocumented behavior from legacy controllers remains.

## Recommended First Execution Slice

### Build Order
- [ ] Wire `web.php`, `mobile.php`, and `api.php` in `app/Domains/Timecards/Providers/TimecardsServiceProvider.php`.
- [ ] Add lifecycle services in `app/Domains/Timecards/Services/` for week logic, entry sync, and status transitions.
- [ ] Build user index, form, and show Livewire pages under `app/Domains/Timecards/Livewire/User/Timecards/`.
- [ ] Add duplicate-week API endpoint and tests.
- [ ] Add observer/hooks for total-hours sync before admin transition work.

### Legacy-to-New Mapping for First Slice
- [ ] `project-manager/app/Http/Controllers/TimecardController.php@index` maps to `app/Domains/Timecards/Livewire/User/Timecards/Index.php`.
- [ ] `project-manager/app/Http/Controllers/TimecardController.php@create|store|edit|update` maps to `app/Domains/Timecards/Livewire/User/Timecards/Form.php` plus `app/Domains/Timecards/Services/TimecardLifecycleService.php` and `app/Domains/Timecards/Services/TimecardEntrySyncService.php`.
- [ ] `project-manager/app/Http/Controllers/TimecardController.php@show` maps to `app/Domains/Timecards/Livewire/User/Timecards/Show.php`.
- [ ] `project-manager/app/Http/Controllers/TimecardController.php@submit|resetToDraft` maps to `app/Domains/Timecards/Services/TimecardLifecycleService.php` surfaced through user Livewire actions.
- [ ] `project-manager/app/Http/Controllers/TimecardController.php@checkExisting` maps to `app/Domains/Timecards/Routes/api.php` plus `app/Domains/Timecards/Services/TimecardWeekService.php`.
