# Feature Gap Sprint Checklist

Source: FEATURE_GAP_PLAN.md

Legend:
- [ ] Not started
- [~] In progress / partial
- [x] Completed

## Sprint 0: Foundation Gate
- [x] Finalize cross-domain permission matrix
- [x] Implement shared audit logging framework and schema
- [x] Finalize notification taxonomy and routing contract
- [x] Add baseline tests for permission allow/deny and audit assertions
- [x] Gate: foundation complete before dependent feature work

## Sprint 1: Project Shell + Access
- [x] User projects list (active/open default)
- [x] User project detail page
- [x] Assigned + broader permitted visibility filters
- [x] Project dashboard shell (cross-domain widget composition)
- [x] Project access management (direct and group assignment)
- [x] Tiered per-project permissions
- [x] Access grant/revoke notifications
- [x] Access change audit trail
- [x] Gate: permission and visibility tests pass

## Sprint 2: Financial Backbone
- [x] Contextual pay rate model with effective-date history
- [x] Burden rate model (global defaults + user overrides)
- [x] Configurable burden components
- [x] Payroll weekly period lifecycle
- [x] Payroll provisional and final runs
- [x] Payroll finalization lock
- [x] Payroll corrections (adjustments + controlled reopen)
- [x] Payroll on-screen output + CSV/PDF export
- [x] Payroll-to-project financial sync
- [x] Core financial reports phase 1
- [x] Project profitability report
- [x] Monthly financial performance report
- [x] Labor cost analysis report
- [x] Material cost analysis report
- [x] Drill-downs (project, month/week, cost type, vendor/supplier)
- [x] CSV export support
- [x] PDF export support
- [x] Gate: reconciliation and snapshot stability tests pass

## Sprint 3: Operations
- [ ] Change orders full workflow
- [ ] Change order statuses and lifecycle transitions
- [ ] Change order attachments
- [ ] Change order client approval tracking
- [ ] Change order labor/material breakdown
- [~] User-facing tasks list-first experience
- [~] User task list route + filters + nav
- [ ] Permission-controlled task editing (status, priority, assignees, progress, notes)
- [ ] Task create/edit/structure actions aligned to explicit permissions
- [ ] Task notification events (assignment, status, reminder, comments, updates)
- [ ] Gate: operations permission workflow tests pass

## Sprint 4: Reporting + Sharing + Vendors
- [ ] Notification preferences full matrix UI (in-app/email/SMS/push)
- [ ] Operational reports: timecard and project activity
- [ ] Saved report templates
- [ ] Scheduled reports (weekly/monthly/manual)
- [ ] Guided custom builder phase 1
- [ ] Operational report delivery (interactive, CSV, PDF, email)
- [ ] Secure document share links (public token + login-required)
- [ ] Share link controls (password, recipient restrictions, explicit expiry, revoke)
- [ ] Share link access logs and admin override
- [ ] Minimal vendor records (active/inactive + duplicate checks)
- [ ] Vendor integration for invoices, stock orders, and report filters
- [ ] Vendor change audit logging
- [ ] Gate: reporting and sharing security tests pass

## Sprint 5: Stabilization + UAT
- [ ] End-to-end regression pass
- [ ] Data migration/seed hardening
- [ ] Dashboard and reporting performance checks
- [ ] Security review for permissions, sharing, and notifications
- [ ] Stakeholder walkthrough and sign-off
- [ ] Release readiness checklist complete

## Cross-Cutting Definition of Done
- [ ] Authorization tests for all allow/deny paths
- [ ] Audit assertions for all mutable operations
- [ ] Notification routing tests against user preference matrix
- [ ] Export parity checks (on-screen vs CSV/PDF)
- [ ] Project dashboard widget visibility regression checks
- [ ] No deferred-scope feature implemented without explicit scope change approval

## Current Snapshot
- [x] Notifications preference baseline and channel controls
- [x] Financial reports foundation (project report + CSV export)
- [~] Payroll backend scaffold complete (models/services/policy/tests)
- [~] User-facing tasks list-first baseline

## Next 10 Recommended Tasks
1. [ ] Add permission-gated in-list task editing for status and priority
2. [ ] Add permission-gated assignee/progress/notes task edits
3. [ ] Implement task notification dispatch for key events
4. [ ] Implement monthly financial performance report
5. [ ] Implement labor cost analysis report
6. [ ] Implement material cost analysis report
7. [ ] Add report drill-down dimensions for phase 1
8. [ ] Add financial report PDF export
9. [ ] Implement payroll on-screen output + PDF export workflow
10. [ ] Implement payroll sync into project financial summaries
