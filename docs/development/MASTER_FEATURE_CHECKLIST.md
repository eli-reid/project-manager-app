# Master Feature Checklist

Generated: 2026-05-01  
Source: FEATURE_GAP_PLAN.md, sprint checklists, gap analysis docs, implementation trackers.

Legend: `[ ]` Not started · `[~]` In progress/partial · `[x]` Complete

---

## Projects
- [ ] User-facing project list/show/mobile routes and views
- [ ] User project lifecycle actions (status updates, archive, copy)
- [ ] Admin manage-access page (role assignment, group/foreman assignment)
- [ ] Admin financial analysis page
- [ ] Admin labor analysis page
- [ ] Admin archive/unarchive workflow
- [ ] Admin quick-action toolbar (add invoice, add labor entry, financial jump)
- [ ] Project Board (Kanban) — user-facing desktop + mobile
- [ ] User project document workflow from project pages
- [ ] Project CSV import/export utilities

---

## Timecards
- [x] User lifecycle (index/create/edit/submit/reset)
- [x] Admin create/edit/show/transition flows
- [ ] Admin bulk actions (approve/reject/delete)
- [ ] Reports/payroll compatibility read models
- [ ] Payroll weekly/multi-week data builder contract
- [ ] CSV/PDF export handlers
- [ ] Timecard reminder/scheduler integration
- [ ] Domain lifecycle services/actions (create/update/submit/approve/reject/reset/bulk)
- [ ] Observer/cache hooks for total-hours sync and cache invalidation

---

## Daily Reports
- [x] Domain foundation (provider, model, policy, permissions, migrations, factories)
- [x] User index/create/edit/submit/show flows
- [ ] Uniqueness constraints for report duplication rules
- [ ] Weather fetch/update integration + fallback/manual override
- [ ] Attachment/photo upload flow
- [ ] Project-user selection behavior in create flow
- [ ] Admin index with filters
- [ ] Admin approve/reject actions with reasons
- [ ] Admin bulk operations
- [ ] Mobile route/component parity
- [ ] Dailies tab integration into project details page
- [ ] Dashboard stats widget
- [ ] Data migration/import command from prototype

---

## Tasks
- [x] Admin CRUD (index/create/edit), categories, templates, hierarchy widget
- [ ] Permission-gated task editing (status, priority, assignees, progress, notes) — per-action policy methods
- [ ] User/mobile task list and status-update surface
- [ ] CSV import/export parity
- [ ] Drag-and-drop reorder (task trees, user + admin)
- [ ] Template "apply to project" workflow
- [ ] Admin task detail page parity
- [ ] Admin reorder endpoint
- [ ] Quick category create endpoint + enhanced selector
- [ ] Inline task rename flow
- [ ] Extended analytics panel (weighted progress, billable summaries)

---

## Change Orders
- [ ] Domain foundation (provider, model, policy, permissions, migrations)
- [ ] Full change order workflow and status lifecycle
- [ ] Attachments
- [ ] Client approval tracking
- [ ] Labor/material breakdown UI

---

## Payroll
- [x] Domain foundation (provider, migrations, models, factories, policy)
- [x] Pay rate types admin UI
- [x] Employee pay rates admin UI + form
- [x] OvertimeCalculationService (FLSA, CA daily, 7th-day)
- [x] PayrollRateResolutionService
- [x] PayRunService / GrossToNetService / TaxWithholdingService
- [x] PayrollStatementBuilderService
- [x] PayRuns admin index/create/show with approve/finalize/void
- [x] Admin payroll timecard review screen
- [x] TimecardDailyReconciliation report
- [x] Payroll notifications (TC/PR/EM/SY/CO)
- [x] Forecasting service, widgets, reports
- [ ] Payroll-to-project financial sync service
- [ ] Third-party integrations (deferred — accounting export first)
- [ ] Migration test in clean database state
- [ ] Validate payroll settings visibility in admin settings editor

---

## Financial Reports
- [x] Project profitability report (CSV export)
- [ ] Monthly financial performance report
- [ ] Labor cost analysis report
- [ ] Material cost analysis report
- [ ] Drill-downs (project, month/week, cost type, vendor/supplier)
- [ ] PDF export for financial reports
- [ ] On-screen vs CSV vs PDF parity tests
- [ ] Reconciliation and snapshot stability tests
- [ ] Saved report templates
- [ ] Scheduled report delivery

---

## Invoices
- [x] Admin CRUD baseline
- [ ] User-facing invoice routes and views
- [ ] Invoice services
- [ ] Material Invoices with AI PDF extraction (Invoice V2)

---

## Stock Orders
- [ ] Domain foundation (provider, permissions, migrations, models, policies)
- [ ] User order index/create/edit/show with dynamic item rows
- [ ] Template browser and create-from-template
- [ ] Mobile-friendly user experience
- [ ] Admin processing queue (approve/order/receive/cancel)
- [ ] Admin template management
- [ ] Navigation integration (sidebar, user nav)
- [ ] N+1 audit and security review

---

## Documents
- [x] Core domain (provider, model, policy, migrations, factories)
- [x] User My Documents CRUD
- [x] Global documents listing
- [x] Promote/demote user document actions
- [x] Project documents tab (full CRUD)
- [x] Admin documents queue + disk usage summaries
- [x] Replace behavior settings
- [ ] Secure share links (public token, password, expiry, revoke)
- [ ] Share link access logs and admin override
- [ ] Per-user/per-role access pivots for fine-grained sharing
- [ ] Download tracking and audit logs
- [ ] Version history browsing (when keep-history is enabled)
- [ ] Bulk actions and retention policy workflows

---

## Dashboard
- [x] Registry-driven dashboard shell exists
- [x] `DashboardWidgetRegistry` service (`app/Core/Dashboard/Services/`)
- [x] `DashboardServiceProvider` registered in `bootstrap/providers.php`
- [x] Dashboard template refactored to registry-driven widget loop
- [x] Announcements widget registered via its provider
- [x] Timecards "My Current Week" widget
- [x] Projects active summary widget
- [x] Scheduler health widget
- [x] Daily Reports summary widget
- [ ] Mobile PWA plan implementation

---

## Notification Preferences
- [x] Backend model/service/settings baseline
- [x] Channel controls
- [ ] Full notification preferences matrix UI (in-app/email/SMS/push per event type)
- [ ] Task notification events (assignment, status, reminder, comments)
- [ ] Operational report delivery notifications
- [ ] Scheduled report delivery

---

## Vendors
- [ ] Domain foundation (provider, model, policy, permissions, migrations)
- [ ] Minimal vendor records (active/inactive, duplicate checks)
- [ ] Vendor integration for invoices, stock orders, and report filters
- [ ] Vendor change audit logging

---

## Clients
- [x] Admin CRUD
- [ ] User-facing client views

---

## Addresses
- [x] Admin CRUD
- [ ] User-facing address management + geocoding

---

## Not Started / Scaffold Only
| Domain | Gap |
|---|---|
| Notes | Scaffold only — no implementation |
| Contacts | Scaffold only — no implementation |
| Equipment | Scaffold only — no implementation |
| Photos (job site) | Scaffold only — no implementation |
| Electrical Calculators (3 types) | Not found in new app |
| Access Logs + IP Blacklist | Not found in new app |
| Admin Labor Entries (timecard overrides) | Not found in new app |
| Application Installer (web wizard) | Not found in new app |
| SMS Integration (Zoom) | Not found in new app |
| cPanel credential rotation | Operator task pending |

---

## Sprint 3 Active (Next Up)
1. [ ] Define explicit task permissions for status, priority, assignee, progress, notes
2. [ ] Update task policy methods per-action
3. [ ] Permission-gated project-tab task editing UI for status/priority
4. [ ] Permission-gated project-tab task editing UI for assignee/progress/notes
5. [ ] Change orders — full workflow
6. [ ] Change order status lifecycle
7. [ ] Change order attachments
8. [ ] Change order client approval tracking
9. [ ] Change order labor/material breakdown

## Sprint 4 Active (Queued)
- [ ] Notification preferences full matrix UI
- [ ] Operational reports (timecard + project activity)
- [ ] Saved/scheduled report templates
- [ ] Secure document share links
- [ ] Minimal vendor records + invoice/stock integration
