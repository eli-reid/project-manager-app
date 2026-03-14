# Feature Domain Migration Checklist

## Objective
- [ ] Migrate remaining prototype features from `project-manager` into `project-manager-app` domain modules.
- [ ] Convert migrated route-facing views to Livewire-first pages.
- [ ] Preserve policy-first authorization, performance guardrails, and Pest coverage.

## Global Standards (Apply To Every Domain)

### 1. Domain Architecture Standard
- [ ] Use `app/Domains/{Feature}` for business feature domains.
- [ ] Use `app/Core/{System}` only for system-level platform capabilities.
- [ ] Keep `app/Core` limited to cross-cutting/system concerns such as users, roles/permissions, auth, settings, and other platform infrastructure.
- [ ] Do not place business feature modules in `app/Core`.
- [ ] Keep each migrated domain in consistent folders: `Models`, `Policies`, `Permissions`, `Providers`, `Services`, `Livewire`, `Routes`, `Database`, `Resources/Views`, `Tests`.
- [ ] Keep controllers thin; place business rules in services/actions.
- [ ] Use model policies for authorization and avoid role-name checks in component/controller logic.
- [ ] Register permissions in the domain provider and rely on centralized permission synchronization.

### 2. Routing Standard
- [ ] Route full-page GET screens to Livewire page components (`index`, `form`, `show` when needed).
- [ ] Keep admin routes under `admin.` naming and permission middleware.
- [ ] Keep user routes under authenticated middleware groups with least privilege.
- [ ] Keep APIs focused on read/query endpoints needed for UI interaction unless full API behavior is required.
- [ ] Preserve existing route naming conventions to avoid breaking links/tests.

### 3. Livewire UI Standard
- [ ] New migrated screens should be Livewire-first (no new Blade-only CRUD pages).
- [ ] Use domain view namespace for Livewire views (example: `domain::livewire.admin.*`).
- [ ] Register explicit Livewire aliases in the domain provider for non-default paths.
- [ ] Validate input in Livewire actions and show clear validation messages.
- [ ] Add loading/disabled states for destructive actions (`wire:loading.attr`, confirmation prompts).
- [ ] Keep reusable UI pieces as domain partials/components when reused by 2+ pages.

### 4. Data and Migration Standard
- [ ] Follow safe migration pattern: check `Schema::hasTable` before create/drop when needed by project conventions.
- [ ] Use ULID keys and relationship conventions already used in app.
- [ ] Add factories for all newly migrated models used in tests.
- [ ] Avoid ad-hoc legacy tables unless explicitly required; prefer normalized domain schema.

### 5. Validation and Authorization Standard
- [ ] Use Form Requests for controller write endpoints.
- [ ] For Livewire write flows, keep validation in component rules.
- [ ] Enforce policy checks in mount/actions for each sensitive operation.
- [ ] Verify non-admin permission paths for all admin-facing domain actions.

### 6. Performance Standard
- [ ] Eager-load relationships for list/detail screens to prevent N+1.
- [ ] Load shared expensive data once at the highest level and pass downward.
- [ ] Keep dashboard/widgets query-light and limit records.
- [ ] Add or update tests around high-risk query-heavy flows where possible.

### 7. Security Standard
- [ ] Keep CSP-safe inline scripts (nonce helper when inline script is necessary).
- [ ] Keep sensitive operations protected by middleware + policy checks.
- [ ] Avoid leaking secrets in logs/errors.
- [ ] Preserve rate limits and auth boundaries for public/shared routes.

### 8. Testing and Quality Gate Standard
- [ ] Minimum test set per migrated domain:
- [ ] Access test (authorized user succeeds).
- [ ] Forbidden test (unauthorized user blocked).
- [ ] Create/update/delete happy paths.
- [ ] At least one non-admin role test.
- [ ] Run `php artisan test --compact <targeted-domain-tests>`.
- [ ] Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- [ ] Re-run targeted tests after formatting.

### 9. Migration Workflow Standard (Per Domain)
- [ ] Step 1: Inventory proto routes/models/services/views for the domain.
- [ ] Step 2: Scaffold domain structure and provider wiring.
- [ ] Step 3: Port core model/service logic with policy-first auth.
- [ ] Step 4: Build Livewire pages and route bindings.
- [ ] Step 5: Add/port tests and factories.
- [ ] Step 6: Run quality gates (tests + pint).
- [ ] Step 7: Mark legacy source paths as migrated in checklist/docs.

### 10. Definition of Ready (Before Starting Any Domain)
- [ ] Dependencies from previous wave are complete.
- [ ] Required permissions are defined.
- [ ] Target routes and Livewire pages are planned.
- [ ] Test strategy for the domain is identified.

## Wave 0 - Platform Foundation
- [ ] Finalize middleware parity in `Core/User` (`password.changed`, active-user enforcement, role preload).
- [ ] Finalize `Core/Security` middleware stack and registration.
- [ ] Verify provider boot order and permission synchronization stability.
- [ ] Confirm routing/middleware baseline for authenticated user vs admin sections.

## Wave 1 - Projects Foundation
### Domains
- [ ] Projects
- [ ] Clients
- [ ] Addresses

### Projects Domain Scope (Top-Level Domain)

#### In Scope (Required)
- [ ] Project lifecycle and status management (create, update, archive/close as defined by business rules).
- [ ] Project-client relationships and ownership constraints.
- [ ] Project address ownership via shared `addresses` table.
- [ ] Project access/assignment model (which users can view/update specific projects).
- [ ] Stock operations attached to projects:
- [ ] Stock orders.
- [ ] Stock invoices.
- [ ] Material invoice flows if distinct from stock invoices.
- [ ] Change orders attached to projects.
- [ ] Project time/labor data linkage:
- [ ] Time entries associated with projects.
- [ ] Labor entry/cost relationships used by finance calculations.
- [ ] Daily reports associated with projects.
- [ ] Financial reporting inputs and project financial summaries.
- [ ] Project documents (upload/list/view/download) and project document associations.
- [ ] Project-level pay-rate/burden type relationships used for costing.

#### Optional / Follow-Up Scope
- [ ] Project document sharing/public link workflows (if retained in final product direction).
- [ ] Project activity/audit timeline screens.
- [ ] Advanced project analytics widgets beyond core reports.

#### Out Of Scope (Keep Outside Projects Domain)
- [ ] Authentication and RBAC engine internals (User/Core responsibility).
- [ ] User profile addresses (User domain responsibility).
- [ ] Global settings engine internals (Settings/Core responsibility).

#### Dependency Notes
- [ ] Projects is a foundation domain: downstream domains must consume project ownership/policy rules, not duplicate them.
- [ ] Changes to project lifecycle/status rules require regression checks in Timecards, Reports, and Documents domains.

### Address Ownership Notes (Required)
- [ ] Use a shared `addresses` table for both project addresses and client addresses.
- [ ] Keep address ownership polymorphic or explicitly typed so addresses can belong to either projects or clients.
- [ ] Keep user profile/home addresses in the User domain and out of the shared project/client address table.
- [ ] Add policy tests verifying users can only access addresses through authorized parent resources.

### Deliverables
- [ ] Port models, services, policies, and permissions.
- [ ] Build Livewire admin pages (index/form/show as needed).
- [ ] Add required API read endpoints for selectors/search.
- [ ] Add relation-integrity and authorization tests.

## Wave 2 - Time and Field Operations
### Domains
- [ ] Timecards
- [ ] DailyReports
- [ ] LaborEntries

### Timecards Domain Scope (Top-Level Domain)

#### In Scope (Required)
- [ ] Weekly timecard lifecycle:
- [ ] Create week entry.
- [ ] Add/edit time entries.
- [ ] Submit weekly timecard.
- [ ] Approval/rejection workflow.
- [ ] Timecard status transitions and lock rules after submission/approval.
- [ ] Per-user timecard ownership and access boundaries.
- [ ] Project-linked time entry capture (including special non-project entries if retained).
- [ ] Validation rules for hours/day/week and date window constraints.
- [ ] Timecard summaries used by payroll/financial reporting pipelines.
- [ ] Mobile-friendly submission/edit flow using Livewire.

#### Optional / Follow-Up Scope
- [ ] Reminder/notification UX enhancements.
- [ ] Bulk-edit and copy-from-previous-week utilities.

#### Out Of Scope (Keep Outside Timecards Domain)
- [ ] Project lifecycle logic (Projects domain responsibility).
- [ ] Core user authentication/2FA/password policies (User/Core responsibility).
- [ ] Global scheduling infrastructure internals (Scheduler/Core responsibility).

#### Dependency Notes
- [ ] Timecards depends on Projects domain for valid project assignment and access checks.
- [ ] Timecards output must remain compatible with Financial Reports and Daily Reports domains.

### Deliverables
- [ ] Port lifecycle logic (entry, submit, approval flows).
- [ ] Build Livewire user/admin pages.
- [ ] Add non-admin permission-path tests.
- [ ] Validate query performance on list/detail screens.

## Wave 3 - Stock and Invoice Operations
### Domains
- [ ] StockOrders
- [ ] StockOrderTemplates
- [ ] StockInvoices
- [ ] MaterialInvoices

### Deliverables
- [ ] Port CRUD, template workflows, and upload/import flows.
- [ ] Convert admin/user interfaces to Livewire.
- [ ] Add API/read models for dynamic form dependencies.
- [ ] Add feature tests for validation, permission, and file flows.

## Wave 4 - Execution and Planning Modules
### Domains
- [ ] Tasks
- [ ] ChangeOrders

### Tasks Domain Architecture Notes (Required)
- [ ] Keep tasks and categories in one domain module (do not split categories into a separate domain).
- [ ] Keep task templates inside the Tasks domain (do not split templates into a separate domain).
- [ ] Use a category tree model with unlimited structural flexibility:
- [ ] `task_categories` table stores category nodes.
- [ ] `parent_id` on `task_categories` supports recursive `cat -> cat -> cat` depth.
- [ ] `tasks` table stores action nodes and references the deepest category leaf.
- [ ] Support task-to-subtask breakdown (`task -> task`) under the same category path.
- [ ] Add `parent_task_id` to `tasks` (nullable) to support nested task actions.
- [ ] Model intent:
- [ ] Categories represent locations/items/context.
- [ ] Tasks represent actions.
- [ ] Example target hierarchy: `Build A -> Unit 2 -> Fire Alarm -> Rough -> Pull Wire` where the first three are categories and the last two are task/subtask nodes.
- [ ] Add explicit depth settings to constrain traversal/build behavior:
- [ ] `tasks.max_category_depth` for category nesting.
- [ ] `tasks.max_task_depth` (or a documented equivalent) for task-level breakdown depth if sub-task decomposition is enabled.
- [ ] Baseline settings for first implementation: `tasks.max_category_depth = 3` and `tasks.max_task_depth = 2`.
- [ ] Enforce combined chain logic (`cat -> ... -> task -> ...`) using both limits.
- [ ] Use these depth settings as inputs to tree-cache generation and cache-key strategy.
- [ ] Add validation guards to reject writes that exceed configured depth limits.
- [ ] Add tests covering depth-limit enforcement and recursive tree retrieval.
- [ ] Keep template operations aligned with the same depth/validation constraints used by live tasks.

### Deliverables
- [ ] Port planning/execution logic and policy rules.
- [ ] Build Livewire boards/lists/forms.
- [ ] Add interaction tests for state changes and permissions.

## Wave 5 - Reporting and Finance
### Domains
- [ ] FinancialReports
- [ ] Reports
- [ ] Calculators

### Deliverables
- [ ] Port reporting query services.
- [ ] Build Livewire filter/export screens.
- [ ] Add tests for report access, filters, and expected aggregates.
- [ ] Validate query optimization on heavy report endpoints.

## Wave 6 - Documents and Sharing
### Domains
- [ ] Documents
- [ ] UserDocuments
- [ ] SharedDocuments

### Deliverables
- [ ] Port upload/view/share/password-verify flows.
- [ ] Implement rate-limited public shared routes.
- [ ] Build Livewire admin/user management views.
- [ ] Add integration tests for share access and protection paths.

## Wave 7 - Admin Operations and Hardening
### Domains
- [ ] AccessLogs
- [ ] Blacklist
- [ ] Install (decide keep/remove)

### Deliverables
- [ ] Port admin operational tooling to domain modules.
- [ ] Convert remaining admin views to Livewire where applicable.
- [ ] Complete final security/performance hardening checks.

## Per-Domain Definition of Done
- [ ] All primary pages in that domain are Livewire-based.
- [ ] Policy/permission checks are complete and tested.
- [ ] Domain-specific tests pass in isolation.
- [ ] No known blocking N+1/query regressions remain.
- [ ] Legacy prototype path for that domain is marked as migrated.

## Progress Tracking
- [ ] Wave 0 complete
- [ ] Wave 1 complete
- [ ] Wave 2 complete
- [ ] Wave 3 complete
- [ ] Wave 4 complete
- [ ] Wave 5 complete
- [ ] Wave 6 complete
- [ ] Wave 7 complete
- [ ] Full migration complete
