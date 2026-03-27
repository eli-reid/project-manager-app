# Stock Orders Implementation Checklist

## Objective
- [ ] Deliver stock orders in project-manager-app with prototype parity and UI/UX improvements.
- [ ] Use Stock domain architecture with Livewire-first pages and policy-first authorization.
- [ ] Apply new design language (Flux/Tailwind patterns used in current app) across all stock flows.

## Design Intent (From Prototype)
- [ ] Fast stock order entry with dynamic item rows.
- [ ] Template-driven ordering for repeat requests.
- [ ] Clear urgency and status visibility.
- [ ] Mobile-friendly experience for field users.
- [ ] Quick list filtering and stats for triage.

## Improvements To Apply (New App)
- [ ] Replace Blade-heavy and redirect-heavy flows with Livewire-first screens.
- [ ] Use policy checks for all sensitive operations.
- [ ] Normalize stock statuses and transition rules.
- [ ] Use eager loading and lightweight queries for index/stats views.
- [ ] Keep responsive UI cohesive with existing admin/user navigation.

## Phase 0 - Domain Foundation
- [ ] Add Stock service provider in domain boot flow.
- [ ] Register stock permissions through PermissionRegistry.
- [ ] Register policies for stock models.
- [ ] Load stock migrations, views, and route groups from provider.
- [ ] Confirm route files are wired:
- [ ] admin routes
- [ ] user web routes
- [ ] mobile routes (or responsive route strategy)
- [ ] API read routes (only where needed)

## Phase 1 - Data Model and Migrations
- [ ] Create stock_orders migration with ULID PK and required indexes.
- [ ] Create stock_order_items migration with ULID PK and required indexes.
- [ ] Create stock_order_templates migration with ULID PK and required indexes.
- [ ] Include project linkage and PO number handling.
- [ ] Include urgency and status columns with clear defaults.
- [ ] Add soft deletes where needed.
- [ ] Add/update factories for all stock models.

## Phase 2 - Domain Models and Policies
- [ ] Implement StockOrder model relationships.
- [ ] Implement StockOrderItem model relationships.
- [ ] Implement StockOrderTemplate model relationships/scopes.
- [ ] Add casts and helper accessors where useful.
- [ ] Create StockOrderPolicy with view/create/update/process/cancel coverage.
- [ ] Create StockOrderTemplatePolicy with ownership/global access rules.
- [ ] Validate non-admin paths explicitly in policy logic and tests.

## Phase 3 - User Flow (Livewire + New Design)
- [ ] Build stock orders index page component.
- [ ] Build stock order create/edit form component with dynamic items.
- [ ] Build stock order show page component.
- [ ] Build template browser component.
- [ ] Build create-from-template component.
- [ ] Keep project or PO assignment rules clear in UI validation.
- [ ] Add loading, disabled, and empty states.
- [ ] Add success and error messaging aligned to current app patterns.

## Phase 4 - Admin Processing Flow
- [ ] Build admin stock orders queue/index.
- [ ] Add status processing actions (approve/order/receive/cancel as defined).
- [ ] Build admin template management screens.
- [ ] Add filtering by status, urgency, requester, and project.
- [ ] Enforce policy checks on all admin actions.

## Phase 5 - Navigation and Integration
- [ ] Add stock links to admin sidebar with permission gates.
- [ ] Add user navigation entry points for stock orders/templates.
- [ ] Integrate stock order references into related invoice/material workflows.
- [ ] Add project-level stock summaries/widgets where appropriate.

## Phase 6 - Performance and Security
- [ ] Audit index/show queries for N+1 issues.
- [ ] Ensure heavy data is loaded once and passed down.
- [ ] Confirm middleware and auth boundaries for admin/user routes.
- [ ] Keep CSP-safe patterns for any inline scripts.

## Phase 7 - Testing and Quality Gates
- [ ] Feature test: authorized user can view own stock orders.
- [ ] Feature test: unauthorized user cannot view restricted stock orders.
- [ ] Feature test: create stock order with multiple items.
- [ ] Feature test: create stock order from template.
- [ ] Feature test: admin processing/status transitions.
- [ ] Feature test: template ownership/global visibility constraints.
- [ ] Run targeted tests with compact output.
- [ ] Run Pint on dirty files.
- [ ] Re-run affected tests after formatting.

## Definition of Done
- [ ] Stock domain routes/pages are fully operational.
- [ ] Desktop and mobile-responsive flows are complete and usable.
- [ ] Policies and permissions enforce all required access rules.
- [ ] Core stock flows covered by passing Pest tests.
- [ ] UI reflects new app design patterns (not prototype styling).
- [ ] No blocking performance regressions detected.
