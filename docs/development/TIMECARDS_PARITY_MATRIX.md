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

## Parity Table

| Area | Legacy Surface | Legacy Behavior | Target in New Domain | Cleanup Required | Priority | Status |
|---|---|---|---|---|---|---|
| User list | TimecardController@index | Own timecards list, totals, current/future week UX | Livewire user index in Timecards domain | Move query/date logic to query action/service | P0 | [ ] |
| User create | create + mobileCreate | Week normalization + duplicate check | Shared Livewire create flow (responsive) | Remove desktop/mobile duplication | P0 | [ ] |
| User store | store + mobileStore | Two payload shapes and duplicated logic | Single create action + normalized request contract | Replace inline validation with dedicated request/rules | P0 | [ ] |
| User edit | edit + mobileEdit | Draft-only edits, owner checks | Shared edit component and lock guards | Centralize lock rules in lifecycle action | P0 | [ ] |
| User update | update | Entry sync/upsert/delete in controller | UpdateTimecard action + entry sync service | Remove nested orchestration from controller | P0 | [ ] |
| User submit | submit + mobileSubmit | Draft + entries required + notifications | Submit action with transition policy | State transitions in one lifecycle service | P0 | [ ] |
| User reset | resetToDraft + mobileResetToDraft | Rejected-only reset to draft | Reset action | Keep transition checks centralized | P1 | [ ] |
| User detail | show + mobileShow | Owner/permission checks + entry loading | Show component with policy-first auth | Remove inline permission strings | P1 | [ ] |
| Duplicate check API | checkExisting | Week-exists lookup endpoint | Read endpoint in Timecards API routes | Reuse normalized week key helper | P2 | [ ] |
| Admin list | Admin TimecardController@index | Filtered admin listing | Livewire admin index/filter page | Move filtering to query object | P0 | [ ] |
| Admin create/edit | create/store/edit/update | Full user/timecard/entries management | Admin form + create/update actions | Separate status transitions from entry edits | P0 | [ ] |
| Admin transitions | approve/reject/resetStatus | Manual status transitions | Approval queue + lifecycle actions | Use single transition ruleset | P0 | [ ] |
| Admin bulk | bulkAction | Approve/reject/delete loops | Bulk action service/actions | Transaction-safe batching + audit hook | P1 | [ ] |
| Admin reports | reports + grouping helpers | Approved-entry reports by user/project/date | Reporting read models with stable API | Keep reporting queries out of write path | P1 | [ ] |
| Payroll/report exports | payrollReport/export CSV/PDF | Multi-week payroll rollups | Shared report builder in report boundary | Formal output contract for downstream use | P1 | [ ] |
| Reminders | sendReminders | Manual reminder trigger | Scheduler/Core integration boundary | Remove reminder orchestration from UI flow | P2 | [ ] |
| Observers/cache | TimecardObserver + TimecardEntryObserver | Update totals, labor costs, clear caches | Domain events/observers in Timecards | Add deterministic cache key strategy + tests | P0 | [ ] |

## Known Gaps to Resolve Early
- UpdateTimecardRequest in legacy is not usable as a migration source (authorize false, empty rules).
- Integration tests in legacy for lifecycle/mobile are placeholders only.
- New app Timecards route files are scaffold-only and currently empty.

## Definition of Parity Complete
- Every P0 row implemented and tested in project-manager-app.
- P1 rows implemented or explicitly deferred with signed-off rationale.
- No undocumented behavior from legacy controllers remains.
