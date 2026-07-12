# Project Manager App Design Spec

## Status
- Canonical architecture and product design spec for `project-manager-app`.
- Replaces the earlier draft baseline with a current-state plus target-state specification.
- Effective date: 2026-07-12.

## 1. Product Definition

### 1.1 Purpose
`project-manager-app` is the operating system for Midstate Electric's project delivery workflow. It centralizes project execution, field reporting, labor capture, documentation, financial support workflows, and operational administration in a single Laravel application.

The long-term product direction is a domain-driven modular monolith with a formal plugin platform so internal teams and external development partners can add functionality without modifying core code.

### 1.2 Primary Users
- Field employees submitting timecards, dailies, project updates, and mobile workflow data.
- Project managers coordinating scope, RFIs, submittals, stock, documents, and execution status.
- Office administrators managing users, permissions, announcements, settings, scheduling, and queue health.
- Payroll and accounting staff consuming approved operational data.
- External plugin developers extending integrations, UI surfaces, workflow automations, and industry-specific capabilities.

### 1.3 Product Surfaces
- Admin web application for management, configuration, and oversight.
- Authenticated user web application for day-to-day operations.
- Mobile/PWA experience for field-first workflows.
- Background job and scheduler infrastructure for asynchronous work.
- Integration surface for first-party and third-party plugins.

## 2. Strategic Architecture Goals

### 2.1 Core Goals
- Keep business capabilities isolated by bounded context.
- Keep cross-cutting concerns in platform-level core systems.
- Make new route-facing UI Livewire-first.
- Prefer registry-driven extension over hard-coded cross-module coupling.
- Allow plugin teams to ship optional functionality behind stable contracts.
- Preserve a deployable Laravel monolith instead of moving to distributed services prematurely.

### 2.2 Non-Goals
- No microservice split in the current architecture plan.
- No direct plugin writes into unrelated domain internals.
- No Blade-only route-facing expansion as the default UI strategy.
- No runtime plugin execution model that bypasses application authorization, queueing, or settings conventions.

## 3. Current-State Architecture Summary

### 3.1 Module Layout
The application already follows a three-part structural split:

- `app/Core/{System}` for cross-cutting platform systems.
- `app/Domains/{Feature}` for business capabilities.
- `app/PlugIns/{Plugin}` for optional integrations and add-on features.

### 3.2 What Is Already Working
- Domain service providers are auto-discovered from `app/Domains/*/Providers/*ServiceProvider.php`.
- Domain modules self-register routes, permissions, Livewire namespaces, views, migrations, notifications, reports, dashboard widgets, scheduler tasks, and project tabs.
- Settings discovery already scans core, domain, and plugin settings classes.
- The codebase already uses registry patterns that are compatible with a plugin ecosystem.

### 3.3 Current Gaps
- Plugin providers are still manually listed in `bootstrap/providers.php`.
- There is no formal third-party plugin manifest, install lifecycle, or activation registry.
- Plugin capabilities are mostly convention-based rather than contract-based.
- There is no canonical version-compatibility policy for external plugins.
- Some internal modules still reference specific plugins directly, which weakens replaceability.

## 4. Canonical System Boundaries

### 4.1 Core Systems
Core systems own platform concerns that multiple business domains depend on.

| Core System | Responsibility |
|---|---|
| Announcement | System-wide notices and announcement surfaces |
| Assets | Shared asset and infrastructure helpers |
| Audit | Audit trail and operational traceability |
| Auth | Roles, permissions, authorization primitives |
| Identity | Authentication, users, user-facing account flows |
| Notification | Notification definitions, channel registry, dispatch orchestration |
| Queue | Queue monitoring and operational controls |
| Scheduler | Recurring task definitions and execution coordination |
| Settings | Central settings storage, discovery, synchronization |
| UI | Shared dashboard and presentation infrastructure |

### 4.2 Business Domains
Domains own business workflows, data, policies, Livewire screens, and route surfaces.

| Domain | Responsibility |
|---|---|
| Accounting | Accounting codes and downstream financial alignment |
| Addresses | Shared address records and reuse across entities |
| ChangeOrders | Change order lifecycle and project change tracking |
| Clients | Client organization records and related operations |
| Dailies | Daily field reporting |
| Documents | Project documents and public/share flows |
| Equipment | Equipment-related workflows |
| Invoices | Invoice entry, tracking, and project financial views |
| Payroll | Payroll rates, runs, audit, and reporting |
| Projects | Central project aggregate and cross-domain anchor |
| Reports | Report catalog and report presentation surfaces |
| RFIs | Request-for-information workflows |
| Stock | Stock orders and material coordination |
| Submittals | Submittal workflow management |
| Tasks | Project work breakdown and execution tasks |
| Timecards | Labor capture, review, and reminder workflows |

### 4.3 Plugins
Plugins own optional capabilities that are not foundational to the base system and must be installable, replaceable, and independently evolvable.

Current built-in plugin examples:
- `Cpanel`
- `WeatherApi`
- `Zoom`

These prove the model, but they should be treated as first-generation internal plugins rather than the final external plugin platform.

## 5. Domain Model and Responsibility Map

### 5.1 Central Aggregate
`Projects` is the operational center of the product. Most feature domains either attach directly to a project or produce data that ultimately rolls up into project execution status.

Project-connected domains include:
- ChangeOrders
- Dailies
- Documents
- Invoices
- RFIs
- Stock
- Submittals
- Tasks
- Timecards

### 5.2 Operational Flow
- Field users create source-of-truth operational records such as timecards and dailies.
- Project workflows consume and organize those records around a project.
- Payroll consumes timecard-derived labor information.
- Accounting consumes financially relevant outputs from payroll, invoices, and related reporting.
- Reports summarize both operational and financial slices of the system.

### 5.3 Core-to-Domain Rule
Core systems may provide services, registries, policies, infrastructure, and presentation primitives to domains. Domains may depend on core contracts. Core systems must not become dumping grounds for business workflows that belong to a domain.

## 6. Routing and UX Architecture

### 6.1 Route Shapes
- Admin routes use the `admin.*` namespace and `/admin/...` URL structure.
- Mobile routes follow the canonical `mobile/{domain}` path shape.
- Authenticated user routes remain available outside admin where business workflows require it.
- Public routes are exceptional and should be scoped explicitly, such as shared-document access.

### 6.2 UI Implementation Standard
- New route-facing screens are Livewire-first.
- Blade remains valid for layouts, partials, emails, low-level UI primitives, and compatibility shims.
- Mobile uses a dedicated shell and navigation contract.

### 6.3 UX Priorities
- Fast access to active project work.
- Minimal friction for field entry on mobile.
- Policy-aware conditional UI rather than role-name branching in views.
- Reusable registry-driven widgets, tabs, and navigation entries instead of duplicative page logic.

## 7. Existing Extension Points

The application already contains the correct architectural pattern for plugin growth: registries and provider-owned registration.

### 7.1 Permission Extension
Providers register permissions through `PermissionRegistryContract`. Duplicate permission keys are ignored with warning logs. This is the correct contract for domain and plugin permission declarations.

### 7.2 Notification Extension
Notification infrastructure is registry-based.

- `NotificationRegistry` stores notification definitions.
- `NotificationChannelRegistry` stores channel implementations.
- Built-in channels register through providers.
- Additional channels can be registered by domains or plugins.

### 7.3 Settings Extension
Settings discovery already scans:
- `app/Core/*/Settings`
- `app/Domains/*/Settings`
- `app/PlugIns/*/Settings`

This makes settings one of the most mature plugin-ready surfaces in the app.

### 7.4 Reporting Extension
`ReportRegistry` lets domains register report definitions by section. This is a stable pattern for plugin-owned reports.

### 7.5 Dashboard Extension
`DashboardWidgetRegistry` enables modules to contribute widgets to dashboard sections. This is a natural plugin surface for operational summaries.

### 7.6 Project Workspace Extension
`ProjectTabRegistry` allows domains to add project-scoped tabs and panels. This is the app's strongest example of domain-level extensibility and should become a formal external plugin contract.

### 7.7 Scheduler Extension
`TaskTypeRegistry` allows modules to register schedulable task handlers with metadata. This supports plugin-provided automations.

### 7.8 Identity Extension
`UserRelationshipRegistry` allows domains to register user-related relationships at runtime. This is useful for plugin-defined user adjacency without patching the user model directly.

## 8. Target Architecture Model

### 8.1 Application Shape
The target system is a plugin-capable modular monolith with three tiers:

1. Platform Core
2. First-Party Domains
3. External Plugin Packages

### 8.2 Ownership Rules
- Core owns contracts, registries, and global infrastructure.
- Domains own business behavior and first-party workflows.
- Plugins own optional capabilities and integrations.
- Plugins may extend domains through published contracts and registries, not private implementation details.

### 8.3 Dependency Rules
- Core cannot depend on any plugin.
- Domains should not depend directly on plugin implementations.
- Plugins may depend on core contracts and approved domain extension contracts.
- Plugin-to-plugin dependencies must be declared explicitly and validated at install time.

## 9. Plugin Platform Specification

### 9.1 Plugin Types
The platform should support three plugin categories:

| Type | Purpose |
|---|---|
| Integration Plugin | Connect to external systems such as messaging, weather, accounting, storage, or communication APIs |
| Workflow Plugin | Add business workflow screens, actions, jobs, reports, or automation |
| UI Plugin | Add widgets, project tabs, dashboards, settings pages, navigation, or view components |

### 9.2 Plugin Packaging Model
Current internal plugins live under `app/PlugIns`. That remains acceptable for first-party code during migration.

For third-party teams, the target install model should be a dedicated top-level plugin directory, for example:

```text
plugins/
    VendorName/
        PluginName/
            plugin.json
            src/
            database/
            resources/
            routes/
            tests/
```

Rationale:
- Keeps external code outside `app/`.
- Separates vendor ownership from first-party code.
- Simplifies install, enable, disable, and removal workflows.

### 9.3 Plugin Manifest Contract
Every external plugin must provide a manifest file.

Recommended manifest fields:

```json
{
  "id": "vendor.plugin-name",
  "name": "Plugin Name",
  "version": "1.0.0",
  "description": "Short plugin summary",
  "provider": "Vendor\\Plugin\\Providers\\PluginServiceProvider",
  "api_version": "1",
  "app_version_constraints": "^1.0",
  "capabilities": [
    "settings",
    "routes.admin",
    "routes.web",
    "notifications.channels",
    "project-tabs",
    "dashboard-widgets",
    "scheduler-tasks"
  ],
  "dependencies": [],
  "permissions": [],
  "settings": [],
  "migrations": true,
  "assets": true,
  "signature": "..."
}
```

### 9.4 Plugin Lifecycle
The platform must support these lifecycle states:
- Discovered
- Verified
- Installed
- Enabled
- Disabled
- Upgrade Pending
- Failed
- Uninstalled

Lifecycle flow:
1. Discover plugin package.
2. Validate manifest and compatibility.
3. Verify signature or trust source.
4. Register plugin record in database.
5. Run plugin migrations.
6. Sync settings and permissions.
7. Enable provider and extension surfaces.
8. Run health checks.
9. Allow disable, upgrade, rollback, or uninstall.

### 9.5 Plugin Service Provider Contract
Plugins must behave like good Laravel modules.

Rules:
- Bind services in `register()`.
- Register routes, views, migrations, Livewire namespaces, and registry contributions in `boot()`.
- Use dependency injection in `boot(...)` for registries and contracts.
- Do not assume other plugins boot first.
- Defer cross-module work through `app()->booted(...)` when ordering matters.
- Never mutate unrelated domain state during provider boot.

### 9.6 Approved Plugin Extension Surfaces
Plugins may extend the application only through approved surfaces.

Initial approved surfaces:
- Settings definitions
- Permission definitions
- Notification definitions
- Notification channels
- Dashboard widgets
- Project tabs and project panels
- Scheduler task types
- Report definitions
- Livewire pages under plugin-owned routes
- Database migrations for plugin-owned tables
- Background jobs and queues

Future approved surfaces:
- Navigation registry
- Document storage drivers
- Export/import providers
- Accounting adapters
- Marketplace metadata

### 9.7 Restricted Surfaces
Plugins must not:
- Edit first-party migrations.
- Patch core or domain models directly.
- Register global middleware without explicit platform approval.
- Write directly into another module's private tables unless a published contract allows it.
- Bypass policies, queue controls, audit expectations, or settings conventions.

## 10. Data and Integration Design

### 10.1 Persistence Rules
- ULID remains the default identifier strategy.
- Each domain owns its own tables and migrations.
- Plugins own their own tables and migration history.
- Cross-domain references should flow through explicit foreign keys or published service contracts.

### 10.2 Settings Model
- Settings are centralized and discoverable.
- Plugin settings are synchronized through the same settings pipeline as first-party modules.
- Sensitive plugin settings must be marked and stored using the same secret-handling rules as the rest of the app.

### 10.3 Background Processing
- Slow or side-effect-heavy plugin actions should run through queues.
- Scheduler-triggered plugin workflows should use the shared scheduler infrastructure rather than ad hoc cron integrations.

### 10.4 Event and Notification Strategy
- Notifications should be registered declaratively.
- Plugin notifications should resolve channels through `NotificationChannelRegistry`.
- Over time, important cross-domain behaviors should publish canonical events so plugins can subscribe without model patching.

## 11. Security and Governance

### 11.1 Security Model
- Authorization is policy-first and permission-first.
- Plugin capabilities must be permission-scoped.
- Plugin installation and activation are admin-only operations.
- Plugin secrets must come from settings or environment-backed configuration, never from code.

### 11.2 Trust Model for External Plugins
External plugins must be treated as trusted-but-verified code.

Minimum controls:
- Signed package verification or allow-listed source registry.
- Declared capability review before enablement.
- Install-time compatibility checks.
- Audit log entries for install, enable, disable, upgrade, and uninstall actions.
- Health reporting for failed boot, migration, or registration.

### 11.3 Operational Guardrails
- Duplicate registry keys must continue to fail safely with logs.
- Plugin boot failures must not corrupt base application boot.
- A disabled plugin must leave the application in a clean, deterministic state.

## 12. Performance and Reliability

### 12.1 Performance Rules
- Registry lookups must be cheap and cached when practical.
- Domain and plugin dashboards must avoid N+1 queries.
- Project tabs must lazy-load or scope expensive data.
- Plugin discovery should be cached after manifest verification.

### 12.2 Reliability Rules
- Installation and upgrades must be transactional where feasible.
- Plugin migrations must be isolated and reversible.
- Queue-based plugin failures must surface through logs and admin diagnostics.
- Health checks should detect missing settings, invalid credentials, or failed registrations.

## 13. UX and Branding Direction

### 13.1 Product Feel
The product should feel like an industrial operations tool, not a generic SaaS dashboard.

Desired qualities:
- Clear hierarchy
- Fast scanning for active work
- Mobile-first field ergonomics
- Strong contrast and reliable status communication
- Minimal ambiguity in actions and workflow state

### 13.2 Brand Direction
Based on the current Midstate Electric identity, the visual system should lean on:
- Deep navy and electric blue for primary surfaces
- Bright green as a controlled accent for action or completion states
- Rounded app-icon geometry translated into cards, containers, and shell treatments
- Clean sans-serif typography with strong legibility in field conditions

### 13.3 Experience Priorities by Surface
- Admin: dense but controlled, with powerful filtering and configuration workflows.
- User web: fast navigation across active project work.
- Mobile/PWA: single-thumb workflows, large targets, offline-safe reads, and minimal form friction.
- Plugin management: explicit capability summaries, health status, and dependency visibility.

## 14. Migration Plan to Full Plugin Platform

### Phase 1: Stabilize Contracts
- Treat existing registries as official extension points.
- Publish contract docs for permissions, settings, project tabs, reports, dashboard widgets, scheduler tasks, and notifications.
- Remove direct domain references to concrete plugin implementations where contracts can replace them.

### Phase 2: Introduce Plugin Runtime
- Add plugin manifest parsing and persistence.
- Add plugin discovery outside `app/`.
- Add plugin enable/disable state management.
- Add compatibility and dependency validation.

### Phase 3: Add Admin Plugin Management
- Upload/install workflow
- Source verification
- Migration execution
- Settings sync
- Health dashboard
- Rollback and uninstall controls

### Phase 4: Support External Teams
- Publish plugin SDK and authoring guide.
- Provide example reference plugins.
- Ship contract tests for plugin validation.
- Define semantic versioning and deprecation policy for plugin APIs.

## 15. Architecture Decisions

### Decision A
The app remains a modular monolith.

### Decision B
Projects remains the central operational aggregate.

### Decision C
Livewire-first is the default for new route-facing UI.

### Decision D
Registry-based composition is the preferred extension mechanism.

### Decision E
External plugins must live outside `app/` and be activated through a formal lifecycle.

### Decision F
Plugins may extend behavior only through explicit contracts, never by relying on private implementation details.

## 16. Definition of Done for Plugin-Ready Architecture

The architecture should be considered plugin-ready when all of the following are true:
- Domain providers and plugin providers are both discovery-driven.
- External plugins can be installed without editing `bootstrap/providers.php`.
- Plugin manifests are validated and versioned.
- Plugin capabilities are visible in admin before activation.
- Core extension surfaces are documented and contract-tested.
- Plugins can register settings, permissions, routes, widgets, tabs, tasks, and notification channels without modifying first-party code.
- Plugin failures degrade safely and observably.

## 17. Canonical References
- `bootstrap/providers.php`
- `app/Domains/Providers/DomainServiceProvider.php`
- `app/Core/Settings/Services/SettingsClassDiscoverer.php`
- `app/Core/Notification/Providers/NotificationServiceProvider.php`
- `app/Core/Auth/Permission/Services/PermissionRegistry.php`
- `app/Core/Scheduler/Services/TaskTypeRegistry.php`
- `app/Core/UI/Dashboard/Services/DashboardWidgetRegistry.php`
- `app/Domains/Projects/Services/ProjectTabRegistry.php`
- `app/Domains/Reports/Services/ReportRegistry.php`
- `docs/development/ARCHITECTURE_ALIGNMENT_CHECKLIST.md`
- `docs/completed-features/BOOT_ORDER_HARDENING.md`
- `docs/completed-features/CORE_MIGRATION_MATRIX.md`
