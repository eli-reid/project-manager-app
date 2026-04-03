# Plan: Feature Gap Analysis — Prototype vs New App

## Summary
Comparing `project-manager` (prototype, Laravel 11, Blade/traditional MVC) against `project-manager-app` (new app, Laravel 12, Livewire 4/Flux UI, DDD architecture) to identify what features need to be ported/built.

---

## Prototype Feature Status vs New App

### ✅ DONE in both (parity achieved)
- Auth (login, register, password reset, force-password-change, 2FA)
- Dynamic Roles & Permissions (RBAC)
- User Management (Admin CRUD)
- App Settings (SQLite dual-layer)
- Announcements (Admin UI + dashboard widget)
- cPanel Email Management + Webmail
- Queue Monitor
- Task Scheduler (Cron UI)
- Timecards (User + Admin + lifecycle + notifications + reminders)
- Stock Orders + Templates (User + Admin)
- Daily Reports (User + Admin + weather integration)
- Documents (Upload / per-user / per-project / global)

---

### ⚠️ PARTIAL in new app (needs completion)
1. **Projects** — Admin CRUD done. No user-facing routes (`web.php`/`mobile.php` are empty). No financial summary views.
2. **Invoices** — Admin CRUD done. No user routes. No services. Prototype had full Material Invoices + AI PDF extraction (Invoice V2).
3. **Clients** — Admin only. Prototype had user-facing client views.
4. **Addresses** — Admin only. Prototype had full address management + geocoding.
5. **Tasks** — Admin-only (CRUD, categories, templates, hierarchy widget). Prototype had user-facing task boards + milestones.
6. **Notification Preferences** — Backend model/service exists. No UI at all.
7. **User Pay Rates / Burden Rates** — Not found in new app. Prototype had UserPayRate, BurdenRate, PayRateType models.

---

### ❌ MISSING (scaffold only or not started)
| Feature | Prototype Status | New App Status |
|---|---|---|
| Change Orders | ✅ Complete | ❌ Scaffold only (empty models + routes) |
| Financial Reports | ✅ Core complete | ❌ Scaffold only |
| General Reports | ✅ Core complete | ❌ Scaffold only |
| Payroll | ✅ Complete (admin labor entries, payroll export) | ❌ Scaffold only |
| Notes | N/A | ❌ Scaffold only |
| Contacts | N/A | ❌ Scaffold only |
| Vendors | N/A | ❌ Scaffold only |
| Equipment | N/A | ❌ Scaffold only |
| Photos (job site) | N/A | ❌ Scaffold only |
| Project Board (Kanban) | ✅ Complete | ❌ Not found |
| Electrical Calculators (3 types) | ✅ Complete | ❌ Not found |
| Access Logs + IP Blacklist | ✅ Complete | ❌ Not found |
| Material Invoices (AI PDF extraction) | ✅ Complete | ❌ Not found |
| Client Pricing Lists | ✅ Complete | ❌ Not found |
| User Documents (Secure Share Links) | ✅ Complete | ⚠️ Documents exist but no secure link sharing |
| Admin Labor Entries (override timecards) | ✅ Complete | ❌ Not found |
| Project Financial Analysis | ✅ Complete | ❌ Not found |
| Project Labor Analysis | ✅ Complete | ❌ Not found |
| Project Access Management | ✅ Complete | ❌ Not found |
| Application Installer (web wizard) | ✅ Complete | ❌ Not found |
| SMS Integration (Zoom) | ⚠️ Service exists | ❌ Not found |
| Dashboard (user + admin + mobile) | ✅ Complete | ⚠️ Basic dashboard exists, no comparison done |

---

## Priority Groupings (suggested)

### P0 — Critical to functional app (core business loop)
1. Projects — user-facing routes + views (list, show, mobile)
2. Project financial/labor analysis views
3. Change Orders — full implementation
4. Payroll — port from prototype (admin labor entries, payroll report)
5. Financial Reports — core reports (project profitability, monthly performance)

### P1 — Important field operations
6. Tasks — user-facing task boards/lists
7. Notification Preferences UI
8. User Pay Rates + Burden Rates
9. Client Pricing Lists
10. Project Access Management

### P2 — Complete feature parity
11. Invoices — user routes + services + AI PDF extraction
12. Project Board (Kanban)
13. Documents — secure share links
14. Admin Labor Entries
15. General Reports

### P3 — Extended features
16. Electrical Calculators
17. Access Logs + IP Blacklist
18. Application Installer
19. Vendors domain
20. Equipment domain
21. Contacts domain
22. Photos domain
23. Notes domain
24. SMS Integration


## Confirmed Scope Decisions
- Projects user-facing list/show pages: Add now
- Project financial/labor analysis: Add now
- Change Orders: Add now
- Payroll, including admin labor entries and payroll reporting/export: Add now
- Core financial reports: Add now
- User-facing tasks and milestone-style tracking: Add now
- Notification preferences UI: Add now
- User pay rates, burden rates, and related rate management: Add now
- Client pricing lists: Future feature
- Project-specific access management: Add now
- Invoice expansion, including advanced material invoice processing: Future feature
- Project board / kanban board: Exclude
- Secure document share links: Add now
- Broader operational reports beyond core financial reporting: Add now (timecard reports, project reports, saved/scheduled reports, custom report builder)
- Electrical calculators: Future feature
- Access logs and IP blacklist tooling: Future feature
- Web-based application installer/setup wizard: Future feature
- Vendors domain: Minimal vendor support now (only enough for invoices and stock orders)
- Equipment domain: Future feature
- Dedicated contacts domain: Exclude
- Job-site photos feature: Future feature
- Notes feature: Future feature
- SMS integration: Future feature
- Client expansion beyond admin-only CRUD: Future feature
- Address expansion beyond admin-only/basic workflows: Future feature
- Richer dashboard parity and dedicated mobile views beyond current baseline: Future feature
- Projects > user project list page: Add now
- Projects > user project detail/show page: Add now
- Projects > dedicated mobile list/show views: Future feature
- Projects > visibility model: Both with filters (assigned by default plus broader permitted views)
- Projects > financial/labor analysis placement: Both. Project view should act as a dashboard that loads widgets from each domain to preserve domain separation, with deeper dedicated analysis screens where needed.
- Projects > project detail/dashboard first version: Include cross-domain widgets now (tasks, documents, dailies, stock orders, change orders, etc.)
- Projects > user list status scope in first version: Active/open projects only
- Change Orders > first version: Full CRUD plus status workflow
- Change Orders > status set: Prototype-style full set (draft, submitted, approved, rejected, implemented, cancelled)
- Change Orders > attachments/supporting documents: Add now
- Change Orders > client approval tracking: Add now
- Change Orders > placement: Both project-context screens and standalone index/list views
- Change Orders > creation/submission rights: Project-permission based users
- Change Orders > approval/rejection rights: Project-permission based approvers
- Change Orders > financial structure in first version: Separate labor and material amount breakdowns
- Payroll > admin labor entries: Add now
- Payroll > reporting and export output: Add now
- Payroll > calculations should use stored user pay rates and burden rates: Add now
- Payroll > data trust model: Both. Allow provisional review using unapproved data, but final payroll must use approved records only.
- Payroll > output formats: On-screen review plus CSV and PDF exports
- Payroll > rate handling: Historical snapshots per payroll period, not recalculation from current rates
- Payroll > pay period in first version: Weekly
- Payroll > payroll-period finalization/lock step: Add now
- Payroll > post-finalization corrections: Both options (default adjustment entries in next period, plus controlled reopen by privileged users)
- Payroll > finalized payroll should automatically sync summarized labor costs into project financials: Add now
- Core financial reports > selected report types: Project profitability, monthly financial performance, labor cost analysis, material cost analysis, cash flow projection, comparative analysis, executive summary
- Core financial reports > delivery strategy: Phase 1 + Phase 2
- Core financial reports > Phase 1 report types: Project profitability, monthly financial performance, labor cost analysis, material cost analysis
- Core financial reports > Phase 2 report types: Cash flow projection, comparative analysis, executive summary
- Core financial reports > Phase 1 drill-down dimensions: By project, by month/week, by cost type, by vendor/supplier
- Core financial reports > Phase 1 export support: CSV and PDF
- Core financial reports > access model: Permission-based financial report access
- Operational reports > first-release categories: Timecard reports, project activity reports, saved report templates, scheduled recurring reports, custom report builder
- Operational reports > outputs: On-screen interactive views, CSV export, PDF export, email delivery for scheduled reports
- Operational reports > access model: Split permissions by action (view, create template, schedule, export)
- Operational reports > template ownership model: Private by default with optional sharing
- Operational reports > first-release schedule frequencies: Weekly, monthly, and manual one-off runs
- Operational reports > custom builder delivery: Phase it (guided builder first, advanced builder later)
- Operational reports > guided custom builder data scope (Phase 1): Timecards, projects, daily reports, stock orders, change orders, payroll summaries, and financial report outputs (read-only)
- Tasks (user-facing) > first-release UI mode: List-first
- Tasks (user-facing) > creation and structure editing should be controlled by explicit permissions (not hardcoded role checks)
- Tasks (user-facing) > default permissions: Read-only by default for non-admin users; create/edit rights granted explicitly by permission
- Tasks (user-facing) > editable fields with permission: Status, priority, assignees, progress percentage, description/notes
- Tasks (user-facing) > dedicated milestone tracking: Future feature (defer)
- Tasks (user-facing) > notifications in first release: Full notifications (assignments, status changes, due reminders, comments, updates)
- Tasks (user-facing) > cross-project 'My Tasks' dashboard: Future feature (defer)
- Secure document share links > access model: Both modes (secure-token public links and login-required shares)
- Secure document share links > default expiration policy: No default expiry (manual revoke or explicit expiry)
- Secure document share links > password protection: Optional per link
- Secure document share links > usage controls in first release: Expiry and revoke only (no max views/download limits)
- Secure document share links > audit logging: Full access logging (view/download events with metadata)
- Secure document share links > recipient restrictions: Support restrictions to specific users/emails in first release
- Secure document share links > governance model: Owner-managed with admin override
- Pay rates and burden management > rate model in first release: Multiple contextual rates (role/project/effective date)
- Pay rates and burden management > burden model: Global default burden rates with user-specific overrides
- Pay rates and burden management > burden components: Admin-configurable component types
- Pay rates and burden management > history policy: Full historical records by effective date (no overwrite)
- Pay rates and burden management > management access: Permission-based financial managers
- Pay rates and burden management > rate visibility: Permission-based visibility (separate from edit rights)
- Pay rates and burden management > change activation: Immediate on save with effective date and audit log (no approval workflow in Phase 1)
- Pay rates and burden management > first-release setup workflow: Manual UI management only (no bulk import)
- Project access management > assignment model: Both direct user assignment and role/group-based assignment
- Project access management > permission model: Tiered explicit action permissions per project
- Project access management > default project access: No one except creator and admins until explicitly granted
- Project access management > change timing in first release: Immediate grants/revokes only (scheduled effective dates deferred)
- Project access management > audit requirements: Full access-change audit logs with before/after values
- Project access management > administration rights: Permission-based project access managers
- Project access management > project dashboard behavior: Hide domain widgets when user lacks domain permission
- Project access management > user notifications: Notify on both access grants and revokes
- Notification preferences UI > first-release channels: In-app, email, SMS, and push/web notifications
- Notification preferences UI > control granularity: Per event type and channel matrix
- Notification preferences UI > defaulting model: Role-based defaults with user override
- Notification preferences UI > quiet hours/snooze: Future feature (defer)
- Notification preferences UI > management model: User self-service with admin override
- Notification preferences UI > change timing: Apply immediately for new notifications
- Notification preferences UI > audit requirements: Track who changed what and when
- Minimal vendor support > vendor model in first release: Minimal full records (name, basic contact fields, active/inactive)
- Minimal vendor support > first-release integration points: Material invoices, stock orders, and reporting filters
- Minimal vendor support > stock order vendor behavior: Vendor selection optional
- Minimal vendor support > management access: Permission-based vendor managers
- Minimal vendor support > data quality: Include duplicate detection checks (name/email/phone)
- Minimal vendor support > audit requirements: Full change audit logging for create/edit/deactivate actions

## Consolidated Execution Plan (Handoff-Ready)

### Phase 0: Foundations (Blocks everything else)
1. Define cross-domain permission matrix and constants for all newly in-scope actions (projects, change orders, payroll, reports, tasks, vendors, notifications, document sharing).
2. Define shared audit logging standard (actor, action, target, before/after, timestamp, metadata).
3. Define notification event taxonomy used by preferences matrix and domain events.

### Phase 1: Project-Centric Core (Can start after Phase 0)
1. Build user-facing Projects list and detail pages (active/open default scope).
2. Implement project visibility model with filters (assigned + broader permitted views).
3. Implement project dashboard shell that composes widgets from domains (no domain logic in Projects domain).
4. Enforce widget-level visibility by project-domain permissions (hide unauthorized widgets).

### Phase 2: Access Control Layer (Parallel with Phase 3 after Phase 0)
1. Implement project access management with both direct user assignment and group/role assignment.
2. Implement tiered per-project action permissions.
3. Implement strict defaults: creator/admin only until explicit grants.
4. Add grant/revoke notifications and full access-change audit trail.

### Phase 3: Financial Backbone (Parallel with Phase 2 after Phase 0)
1. Implement pay rate + burden management:
   - contextual rates by role/project/effective date
   - global burden defaults + user overrides
   - admin-configurable burden components
   - full effective-date history (immutable prior records)
2. Implement payroll:
   - weekly periods
   - admin labor entries
   - provisional review + final approved-data run
   - period finalization/lock
   - correction model (next-period adjustments + privileged reopen)
   - on-screen + CSV/PDF outputs
   - automatic sync of finalized payroll labor summaries into project financials
3. Implement core financial reports:
   - Phase 1: project profitability, monthly performance, labor analysis, material analysis
   - drill-downs: project, month/week, cost type, vendor/supplier
   - exports: CSV/PDF
   - permission-based access

### Phase 4: Change Orders + Task Operations (After Phase 1 and Phase 2)
1. Implement change orders full workflow:
   - CRUD + statuses (draft/submitted/approved/rejected/implemented/cancelled)
   - labor/material breakdowns
   - client approval tracking
   - attachments
   - project-context + standalone list views
   - permission-based create/approve flows
2. Implement user-facing tasks:
   - list-first experience
   - permission-controlled create/edit/structure changes
   - read-only default for non-admin users
   - editable fields per confirmed scope (status, priority, assignee, progress, notes)
   - full task notification events

### Phase 5: Reporting + Sharing + Vendors (After Phase 3)
1. Implement operational reports stack:
   - timecard + project activity reports
   - saved templates + scheduled recurring reports
   - custom builder Phase 1 (guided)
   - outputs: interactive UI + CSV/PDF + scheduled email delivery
   - schedule frequencies: weekly/monthly/manual one-off
   - template ownership: private-by-default + optional sharing
   - action-split permissions
2. Implement secure document share links:
   - both public token and login-required modes
   - optional passwords
   - no default expiry (manual revoke or explicit expiry)
   - recipient restrictions (specific users/emails)
   - full access logs
   - owner managed with admin override
3. Implement minimal vendor support:
   - minimal full vendor records
   - usage in invoices + optional stock-order vendor + report filters
   - duplicate checks
   - permission-based vendor managers
   - full audit logs

### Phase 6: Deferred/Future (Explicitly Out of first implementation wave)
1. Dedicated mobile project views.
2. Dedicated milestone entity for tasks.
3. Cross-project My Tasks dashboard.
4. Quiet hours/snooze for notifications.
5. Financial reports Phase 2: cash flow projection, comparative analysis, executive summary.
6. Invoice expansion/AI extraction, client expansion, address expansion, calculators, access-log tooling, installer, equipment, photos, notes, SMS enhancements.
7. Excluded items: project board/kanban, standalone contacts domain.

### Verification Strategy (Per phase)
1. Permission checks: verify positive and negative access per action.
2. Audit checks: verify write-on-change records with before/after snapshots.
3. Notification checks: verify event-to-channel routing respects user matrix.
4. Financial integrity checks: payroll snapshots remain stable after rate changes.
5. Report checks: exports match on-screen filtered totals.
6. Project dashboard checks: widget visibility correctly hides unauthorized domains.

### Delivery Sequence Recommendation
1. Build Phase 0, then Phase 1 + Phase 2 together.
2. Start Phase 3 as soon as permission and audit primitives are ready.
3. Start Phase 4 once project dashboard shell and access rules are in place.
4. Start Phase 5 once financial and notification primitives are proven.
5. Keep Phase 6 as explicit backlog, not hidden scope.


### Dependency Map (What Blocks What)

#### A. Global Primitives (Hard blockers)
1. Permission matrix + constants:
   - Blocks: project access, project widget visibility, tasks permissions, change order approvals, report access, vendor management, rate management.
2. Audit logging framework:
   - Blocks: project access auditing, vendor change audits, rate-change history confidence, share-link access logs, preference-change logs.
3. Notification event taxonomy + routing layer:
   - Blocks: notification preferences matrix, task notifications, access grant/revoke notifications, scheduled report delivery behavior.

#### B. Core Domain Dependencies
1. Projects user shell (list/detail/dashboard):
   - Depends on: permission matrix.
   - Unblocks: domain widgets, project-context change orders, project-context tasks, project financial summaries.
2. Project access management:
   - Depends on: permission matrix + audit framework.
   - Unblocks: safe rollout of all project-scoped features and widget visibility enforcement.

#### C. Financial Chain Dependencies
1. Pay rates + burden management:
   - Depends on: permission matrix + audit framework.
   - Unblocks: payroll calculations, labor costing, financial reports.
2. Payroll:
   - Depends on: pay/burden model + historical rates + approvals model.
   - Unblocks: project financial sync, labor analysis report accuracy, operational payroll reports.
3. Core financial reports (Phase 1):
   - Depends on: payroll outputs + project cost sync + vendor linkage for material analysis.
   - Unblocks: executive financial visibility and downstream report builder data quality.

#### D. Operations Chain Dependencies
1. Change orders:
   - Depends on: project shell + project permissions + audit framework.
   - Unblocks: project dashboard financial deltas and operational reporting coverage.
2. User-facing tasks:
   - Depends on: project shell + task permissions + notification routing.
   - Unblocks: project activity reporting and task-driven dashboard visibility.

#### E. Reporting & Sharing Dependencies
1. Notification preferences UI:
   - Depends on: notification taxonomy/routing.
   - Unblocks: user-controlled routing for task/access/report events.
2. Operational reports stack:
   - Depends on: stable source domains (timecards, projects, tasks, change orders, payroll summaries) + permission model.
   - Unblocks: scheduled/reporting workflows and custom builder Phase 1 value.
3. Secure document share links:
   - Depends on: document ownership model + audit framework + permission model.
   - Unblocks: external collaboration and secure file delivery workflows.
4. Minimal vendor support:
   - Depends on: permission model + audit framework.
   - Unblocks: invoice vendor quality, stock order vendor association, vendor-filtered reporting.

#### F. Parallel Work Lanes (Safe concurrency)
1. Lane 1 (Security foundations): permissions, audit framework, notification taxonomy.
2. Lane 2 (Project foundation): project list/detail/dashboard shell.
3. Lane 3 (Finance): pay/burden model and payroll.
4. Lane 4 (Operations): change orders and tasks (after Lane 2 + Lane 1 readiness).
5. Lane 5 (Reporting/sharing): operational reports, document sharing, vendor support (after Lane 1 + relevant source-domain readiness).

#### G. Critical Path (Shortest path to high-value release)
1. Permissions + audit + notification taxonomy.
2. Project shell + access management.
3. Pay/burden + payroll + project financial sync.
4. Core financial reports Phase 1.
5. Change orders + tasks.
6. Operational reports + secure sharing + vendor support.

#### H. Risk Concentration Nodes
1. Permission complexity risk:
   - Mitigation: finalize action matrix before implementation begins.
2. Financial consistency risk:
   - Mitigation: immutable rate history + payroll finalization tests + sync reconciliation checks.
3. Notification noise risk:
   - Mitigation: enforce preference defaults and per-event channel matrix tests.
4. Reporting correctness risk:
   - Mitigation: compare report exports to source aggregates on every filter dimension.


## Build Roadmap Checklist (Execution Kickoff)

### 1. Kickoff Setup Checklist
1. Confirm implementation wave includes only in-scope features from this plan.
2. Freeze deferred and excluded scope into backlog labels so they do not leak into Phase 1 work.
3. Assign owners per lane:
   - Lane A: security foundations
   - Lane B: project shell and access
   - Lane C: finance
   - Lane D: operations
   - Lane E: reporting/sharing/vendors
4. Define branching strategy and merge cadence for parallel lanes.
5. Define acceptance template used by every feature ticket (scope, permissions, audits, notifications, tests).

### 2. Foundation Readiness Checklist (Phase 0 Gate)
1. Permission matrix finalized and approved:
   - Project actions
   - Change order actions
   - Task actions
   - Payroll actions
   - Financial report actions
   - Operational report actions
   - Vendor management actions
   - Document sharing actions
   - Notification preference actions
2. Audit schema and helper layer implemented and reviewed.
3. Notification taxonomy and routing contract finalized.
4. Test scaffolding prepared for permission denial/allow checks.
5. Go/No-Go Gate: Do not start dependent feature coding until all above are complete.

### 3. Phase 1 Checklist: Project Shell + Access
1. User-facing project list implemented.
2. User-facing project detail implemented.
3. Active/open default list behavior implemented.
4. Assigned + broader permitted filter behavior implemented.
5. Project dashboard shell implemented with domain widget composition points.
6. Project access management implemented:
   - Direct user assignment
   - Group/role-based assignment
   - Tiered action permissions
   - Strict defaults (creator/admin only)
7. Widget visibility enforces per-domain permissions.
8. Access grant/revoke notifications implemented.
9. Full access-change audit trail implemented.
10. Phase Gate: Permission and visibility tests pass.

### 4. Phase 2 Checklist: Financial Backbone
1. Rate management implemented:
   - Contextual pay rates
   - Burden defaults + overrides
   - Configurable burden components
   - Effective-date history (immutable)
2. Payroll implemented:
   - Weekly periods
   - Admin labor entries
   - Provisional and final runs
   - Finalization lock
   - Correction model (adjust + controlled reopen)
   - CSV/PDF + on-screen output
3. Payroll sync into project financial summaries implemented.
4. Core financial reports Phase 1 implemented:
   - Project profitability
   - Monthly performance
   - Labor analysis
   - Material analysis
5. Drill-down and export rules implemented.
6. Financial permissions and audits validated.
7. Phase Gate: Snapshot stability and reconciliation tests pass.

### 5. Phase 3 Checklist: Operations Features
1. Change orders implemented end-to-end:
   - Full statuses and lifecycle
   - Labor/material breakdowns
   - Client approval tracking
   - Attachments
   - Project + standalone views
2. User-facing tasks implemented:
   - List-first UI
   - Read-only defaults
   - Permission-controlled editing/creation
   - Field edit scope per plan
   - Notification events per plan
3. Phase Gate: Operations workflows pass role and permission tests.

### 6. Phase 4 Checklist: Reporting, Sharing, Vendors
1. Notification preferences UI implemented:
   - In-app/email/SMS/push
   - Per-event channel matrix
   - Role defaults + user override
   - Admin override
   - Audit logging
2. Operational reports stack implemented:
   - Timecard and project reports
   - Saved templates
   - Scheduling (weekly/monthly/manual)
   - Guided custom builder Phase 1
   - CSV/PDF and scheduled email outputs
3. Secure document share links implemented:
   - Public token and login-required modes
   - Optional password
   - Recipient restrictions
   - Revoke and explicit expiry
   - Access logs
4. Minimal vendor support implemented:
   - Vendor records
   - Invoice integration
   - Optional stock-order linkage
   - Reporting filters
   - Duplicate checks
   - Audit logs
5. Phase Gate: Reporting and sharing security tests pass.

### 7. Cross-Cutting Quality Checklist (Every Phase)
1. Authorization tests for allow and deny paths.
2. Audit trail assertions for all state changes.
3. Notification routing tests with user preference matrix.
4. Export parity checks (on-screen totals match CSV/PDF totals).
5. Regression checks for project dashboard widget visibility.

### 8. Launch Readiness Checklist
1. All phase gates completed.
2. No unresolved critical risks in permission, finance, or reporting domains.
3. Backlog explicitly updated with deferred features:
   - Mobile project views
   - Milestone entity
   - My Tasks dashboard
   - Quiet hours/snooze
   - Financial reports Phase 2
   - Other deferred items from scope decisions
4. Stakeholder walkthrough completed on:
   - Projects
   - Access management
   - Payroll and financial reports
   - Change orders and tasks
   - Notification preferences
   - Secure document sharing
5. Release checklist signed off.

### 9. Suggested First Ticket Batch (Day 1 Start)
1. Create permission matrix ticket set.
2. Create audit framework ticket set.
3. Create notification taxonomy ticket set.
4. Create project shell scaffolding tickets.
5. Create project access management tickets.






























































---

## CHECKPOINT: Resume Here

**Current Session Status**: Scope confirmation deep-dive, item-by-item.

**Completed Feature Deep-Dives**:
1. ✅ Projects user-facing scope — LOCKED
2. ✅ Change Orders workflow scope — LOCKED  
3. 🔄 Payroll — MOSTLY LOCKED, need to finish:
   - Payroll finalization/lock step (yes/no)
   - Payroll correction workflow
   - Write payroll summaries back to project costs (yes/no)

**Next Features to Break Down (in this order)**:
1. Core financial reports (which report types + drill-downs)
2. Broader operational reports (timecard/project/custom)
3. Tasks user-facing experience (boards/lists/workflow)
4. Notification preferences UI
5. User pay rates, burden rates, rate management
6. Project access management
7. Secure document share links
8. Minimal vendor support

**Resume Action**: 
- Ask the Payroll finalization question (was interrupted mid-question)
- Continue with payroll correction workflow
- Then move to financial reports


















































## Sprint Plan (Option 1)

### Sprint 0: Foundation Gate (1-2 weeks)
1. Finalize and implement permission matrix constants across domains.
2. Implement shared audit logging framework and schema.
3. Finalize notification taxonomy and routing contract.
4. Add baseline test harness for permission allow/deny and audit assertions.
5. Exit criteria:
- Permission checks working in at least one pilot domain.
- Audit writes verified for create/update/delete events.
- Notification events routable through channel abstraction.

### Sprint 1: Project Shell + Access (2 weeks)
1. Build user project list and detail views with active/open default.
2. Implement assigned plus broader-permitted filtering.
3. Build project dashboard shell with domain widget slots.
4. Implement project access management (direct + group assignment, tiered permissions).
5. Implement widget hiding based on domain permissions.
6. Add access grant/revoke notifications and audits.
7. Exit criteria:
- Project shell usable by non-admin users.
- Access grants and revokes enforce immediately.
- Permission and visibility tests pass.

### Sprint 2: Financial Backbone (2-3 weeks)
1. Implement pay rate and burden management with effective-date history.
2. Implement payroll lifecycle, lock/finalize, corrections, and exports.
3. Implement automatic payroll labor sync to project financial summaries.
4. Implement Core Financial Reports Phase 1 (profitability, monthly, labor, material).
5. Add drill-downs and CSV/PDF exports.
6. Exit criteria:
- Payroll snapshots are stable after rate changes.
- Financial report totals reconcile with source records.
- Financial permission tests pass.

### Sprint 3: Operations (2 weeks)
1. Implement change orders full workflow and attachments.
2. Implement user-facing tasks list-first UI and permissioned edits.
3. Implement task notification events per preference matrix.
4. Exit criteria:
- Change order lifecycle works end-to-end.
- Task permissions enforce read-only defaults and explicit overrides.
- Operations workflow tests pass.

### Sprint 4: Reporting, Sharing, Vendors (2-3 weeks)
1. Implement notification preferences UI (event x channel matrix, role defaults, admin override).
2. Implement operational reports, templates, scheduling, and guided builder Phase 1.
3. Implement secure document share links with logs and restrictions.
4. Implement minimal vendor support for invoices, stock orders, and report filters.
5. Exit criteria:
- Preference changes apply immediately.
- Scheduled reports deliver correctly and export parity is verified.
- Share links and vendor actions are fully audited.

### Sprint 5: Stabilization + UAT (1-2 weeks)
1. End-to-end regression across all in-scope features.
2. Data migration or seed hardening for new entities.
3. Performance checks on reports and dashboard composition.
4. Security review for permissions, share links, and notifications.
5. Exit criteria:
- UAT sign-off completed.
- Critical defects resolved.
- Release checklist signed.

## Jira-Style Breakdown (Option 2)

### Epic E0: Platform Foundations
1. Story E0-S1: Permission matrix and constants.
Acceptance Criteria:
- All in-scope actions mapped to explicit permissions.
- No role-name hardcoding required for authorization decisions.
2. Story E0-S2: Audit framework.
Acceptance Criteria:
- Generic audit records include actor, action, target, before, after, timestamp.
- Audit helper is reusable across domains.
3. Story E0-S3: Notification taxonomy.
Acceptance Criteria:
- Event catalog defined for tasks, access, payroll, reports, sharing.
- Routing supports in-app, email, SMS, push channel flags.

### Epic E1: Projects and Access
1. Story E1-S1: User project list and detail views.
Acceptance Criteria:
- Active/open projects shown by default.
- Detail page loads for authorized non-admin users.
2. Story E1-S2: Project dashboard shell.
Acceptance Criteria:
- Dashboard supports domain widget composition points.
- Unauthorized widgets are hidden.
3. Story E1-S3: Project access management.
Acceptance Criteria:
- Direct and group-based assignments supported.
- Tiered per-project permissions supported.
- Grant and revoke actions audited.
4. Story E1-S4: Access notifications.
Acceptance Criteria:
- Users receive grant and revoke notifications.
- Notification delivery respects preference matrix.

### Epic E2: Rates, Payroll, and Financial Reports
1. Story E2-S1: Contextual pay and burden rates.
Acceptance Criteria:
- Effective-date rate history preserved immutably.
- Burden defaults and user overrides supported.
2. Story E2-S2: Payroll lifecycle and locking.
Acceptance Criteria:
- Weekly payroll periods supported.
- Finalization lock and correction flows implemented.
3. Story E2-S3: Payroll exports and project sync.
Acceptance Criteria:
- On-screen, CSV, and PDF outputs available.
- Finalized payroll sync updates project financial summary data.
4. Story E2-S4: Core Financial Reports Phase 1.
Acceptance Criteria:
- Profitability, monthly, labor, and material reports available.
- Drill-downs and exports match source aggregates.

### Epic E3: Operations Workflows
1. Story E3-S1: Change orders workflow.
Acceptance Criteria:
- Full status lifecycle supported.
- Labor/material breakdown and client approval tracked.
- Attachments supported.
2. Story E3-S2: User-facing tasks.
Acceptance Criteria:
- List-first tasks UI available.
- Read-only default enforced for non-admin users.
- Explicit permissions enable creation/edit/structure actions.
3. Story E3-S3: Task notifications.
Acceptance Criteria:
- Assignment, status, reminder, and update events emitted.
- Delivery channels honor user preferences.

### Epic E4: Reporting and Preferences
1. Story E4-S1: Notification preferences UI.
Acceptance Criteria:
- Event-by-channel matrix editable by users.
- Role defaults seeded and admin override supported.
- Preference changes audited.
2. Story E4-S2: Operational reports core.
Acceptance Criteria:
- Timecard and project activity reports implemented.
- Templates and scheduling (weekly, monthly, manual) implemented.
3. Story E4-S3: Guided custom builder Phase 1.
Acceptance Criteria:
- Guided filtering, sorting, grouping supported.
- Data scope matches approved domains.
4. Story E4-S4: Scheduled report outputs.
Acceptance Criteria:
- Scheduled delivery supports in-app and email flows.
- CSV and PDF exports reconcile with on-screen totals.

### Epic E5: Sharing and Vendors
1. Story E5-S1: Secure document share links.
Acceptance Criteria:
- Public-token and login-required modes supported.
- Optional password and recipient restrictions supported.
- Access logs captured for view and download events.
2. Story E5-S2: Share governance.
Acceptance Criteria:
- Owners can manage links.
- Admin override controls available.
3. Story E5-S3: Minimal vendor records.
Acceptance Criteria:
- Minimal full vendor profile supported.
- Duplicate checks on name/email/phone.
- Vendor actions audited.
4. Story E5-S4: Vendor integration points.
Acceptance Criteria:
- Vendor required/linked for invoice workflows as defined.
- Optional vendor on stock orders supported.
- Vendor-based report filters available.

### Cross-Epic Definition of Done
1. Authorization tests exist for allow and deny paths.
2. Audit assertions exist for every mutable operation.
3. Notification routing tests pass with preference matrix.
4. Export parity tests validate on-screen equals CSV/PDF totals.
5. No deferred-scope feature added without explicit scope change approval.
