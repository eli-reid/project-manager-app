# Dailies Migration Master Checklist

## How to Use
- Mark items complete as they are done.
- Keep this file as the single source of truth for rollout status.
- If scope changes, update this checklist first.

## Phase 0 - Discovery and Alignment
- [ ] Confirm migration objective: prototype parity plus architecture improvements.
- [ ] Confirm source of truth repositories and branches.
- [ ] Freeze prototype feature set for migration baseline.
- [ ] Capture current prototype flows (index, create, edit, submit, approve, reject, show, delete).
- [ ] Confirm required roles and permission matrix (admin, PM, foreman, worker).
- [ ] Confirm weather and attachment requirements for MVP vs post-MVP.
- [ ] Define non-goals for initial cutover.
- [ ] Define acceptance criteria for each phase.

## Phase 1 - Domain Foundation (Scaffold)
- [x] Create `app/Domains/Dailies/Providers/DailiesServiceProvider.php`.
- [x] Register policy for `DailyReport` model.
- [x] Load Dailies migrations and views from domain.
- [x] Register Dailies Livewire components (admin + user placeholders).
- [x] Wire `web`, `mobile`, `admin`, and `api` route groups.
- [x] Create `DailyPermissions` definitions.
- [x] Register Dailies permissions through `PermissionRegistry`.
- [x] Create `DailyReport` model with status constants and casts.
- [x] Create initial `DailyReportPolicy` using policy-first authorization.
- [x] Create initial migration for `daily_reports` table.
- [x] Add baseline domain tests for route auth and component access.
- [x] Run Pest tests for Dailies scaffold.
- [x] Run Pint on changed PHP files.

## Phase 2 - Data Model and Domain Rules Hardening
- [x] Normalize daily report status lifecycle (`draft`, `submitted`, `approved`, `rejected`).
- [x] Ensure status naming consistency in all queries and metrics.
- [x] Define final handling for custom project reports (`project_id` nullable + `custom_project_name`).
- [ ] Add/confirm uniqueness constraints for report duplication rules.
- [ ] Finalize required nullable/non-nullable fields.
- [ ] Define weather storage strategy (single source of truth).
- [ ] Define attachment/photo schema strategy.
- [x] Add domain services for report lifecycle and validations.
- [x] Replace controller-style rule sprawl with Form Request or validated action patterns.
- [ ] Add unit tests for key rule edge cases.
- [ ] Add migration tests for fresh install and schema integrity.

## Phase 3 - Core User Workflows
- [x] Implement user index/list with filters.
- [x] Implement create report flow.
- [x] Implement edit report flow with status guards.
- [x] Implement draft vs submit actions.
- [x] Implement show/details view.
- [x] Implement safe delete rules.
- [ ] Implement project-user selection behavior.
- [ ] Add Livewire validation and loading/error states.
- [x] Add tests for create/edit/submit/delete happy and failure paths.

## Phase 4 - Admin and Approval Workflows
- [ ] Implement admin index with filters.
- [ ] Implement approve/reject actions with reasons.
- [ ] Implement admin visibility and policy checks for cross-user/project scope.
- [ ] Add bulk operation requirements (if in-scope for initial rollout).
- [ ] Add notifications/events hooks for status changes (if required).
- [ ] Add tests for admin workflows and authorization boundaries.

## Phase 5 - Integrations and UX Parity
- [ ] Add weather fetch/update integration in Dailies domain.
- [ ] Add weather fallback/manual override behavior.
- [ ] Add attachment/photo upload flow (if in-scope).
- [ ] Integrate Dailies tab into project details page.
- [ ] Add dashboard cards/widgets for daily stats (if in-scope).
- [ ] Add mobile route/component parity for Dailies.
- [ ] Validate dark/light theme and responsive behavior.
- [ ] Add tests for integration paths and rendering.

## Phase 6 - Data Migration and Reconciliation
- [ ] Build import command from prototype daily reports.
- [ ] Map legacy statuses/fields to new schema.
- [ ] Map weather data and custom project reports.
- [ ] Add idempotency for repeatable imports.
- [ ] Add dry-run mode with summary output.
- [ ] Add reconciliation report (counts by status/project/date/user).
- [ ] Execute staging migration dry-run.
- [ ] Fix mismatches and rerun until reconciled.

## Phase 7 - Cutover and Stabilization
- [ ] Enable Dailies navigation in new app.
- [ ] Run full targeted regression test suite.
- [ ] Validate role-based access in staging with real users.
- [ ] Confirm operational runbook for rollback.
- [ ] Execute production cutover window.
- [ ] Monitor logs/errors/performance for 24-72h.
- [ ] Triage and patch post-cutover issues.
- [ ] Decommission legacy daily routes once stable.

## Cross-Phase Quality Gates
- [x] Use policy-first authorization only.
- [x] Avoid N+1 queries and load related data at the right level.
- [x] Keep domain boundaries clean (model/service/action/policy separation).
- [x] Ensure every change is covered by minimum targeted Pest tests.
- [x] Keep migrations safe for fresh installs.
- [x] Keep permissions synchronized and verifiable.
- [x] Run `vendor/bin/pint --dirty --format agent` on changed PHP files.

## Tracking
- Current active phase: [ ] 0 [ ] 1 [ ] 2 [x] 3 [ ] 4 [ ] 5 [ ] 6 [ ] 7
- Overall progress (%): [ ] 0-25 [x] 26-50 [ ] 51-75 [ ] 76-99 [ ] 100
- Blockers:
- Notes:
