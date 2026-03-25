# Dailies Phase 1 Checklist

## Scope
- Establish Dailies domain foundation in `project-manager-app`.
- Deliver a working, test-covered skeleton that follows the existing domain architecture.
- Do not migrate legacy data yet (covered in later phases).

## Deliverables
- [x] Create `DailiesServiceProvider` and auto-register routes/views/migrations/policies/permissions.
- [x] Create `DailyReport` model in domain namespace with status constants and core casts.
- [x] Create `DailyReportPolicy` with policy-first authorization methods.
- [x] Create `DailyPermissions` definitions and register with `PermissionRegistry`.
- [x] Add initial domain migration for `daily_reports` table.
- [x] Add user/admin route groups for Dailies domain.
- [x] Add placeholder Livewire index/show/form components (admin + user) for first wired path.
- [x] Add baseline views for new components.
- [x] Add feature tests validating route protection, policy access, and component rendering.
- [x] Run targeted tests and fix failures.
- [x] Run Pint formatting for changed PHP files.

## Phase 1 Data Model (Initial)
- [x] `daily_reports.id` ULID primary key.
- [x] `project_id` nullable foreign key.
- [x] `custom_project_name` nullable string.
- [x] `user_id` required foreign key (worker).
- [x] `submitted_by_id` nullable foreign key.
- [x] `report_date` date with index.
- [x] `status` default `draft` with index.
- [x] JSON fields: `work_performed`, `materials_used`, `equipment_used`, `safety_issues`, `delays`, `visitors`.
- [x] Weather snapshot fields (minimal): `weather_condition`, `temperature`, `temperature_unit`.
- [x] Hours summary fields: `total_regular_hours`, `total_overtime_hours`, `total_hours`.
- [x] `additional_notes`, `rejection_reason` nullable text.
- [x] `created_at` and `updated_at`.

## Definition of Done
- [x] Domain boots without errors in local environment.
- [x] Permissions are discoverable via registry sync flow.
- [x] Authenticated users with permissions can access index routes.
- [x] Unauthorized users are correctly forbidden.
- [x] Tests pass for the new Dailies scaffold.
- [x] No lint/format issues in changed PHP files.

## Out of Scope (Phase 2+)
- Legacy data import and reconciliation.
- Full workflow parity with old controller implementation.
- Project tab integration and advanced UI/filters.
- Weather provider integration and historical weather timelines.
- Attachments/photo storage workflow.
