I can convert it to Markdown, but I can’t change a Word document *into* a Markdown file directly inside Word. What I can do is **give you the Markdown text** so you can copy/paste it into a `.md` file (or a Markdown editor).

Here’s the Markdown version of your document:

````markdown
# Payroll System Specification

01 — Payroll System Spec

- **Version:** 1.0  
- **Date:** 2026-04-11  
- **Author:** Elijah M. Reid  
- **Status:** Draft  

## 1. Purpose

This document defines the functional and technical specification for a modular payroll processing system designed for construction and field-operations companies. The system handles timecard ingestion, pay calculation, tax withholding, union/prevailing-wage compliance, direct deposit, and reporting—end to end.

## 2. Scope

| In Scope | Out of Scope |
|---|---|
| Employee master data management | Benefits enrollment UI |
| Timecard collection & approval | General-ledger accounting |
| Gross-to-net pay calculation | Recruitment / onboarding |
| Tax withholding (federal, state, local) | 401(k) plan administration |
| Prevailing wage & certified payroll | Workers' compensation claims |
| Direct deposit / check generation |  |
| Audit trail & compliance logging |  |
| Role-based access control |  |

## 3. System Overview

### 3.1 Architecture

```text
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Timecard    │────▶│  Payroll     │────▶│  Disbursement│
│  Subsystem   │     │  Engine      │     │  Module      │
└──────────────┘     └──────────────┘     └──────────────┘
       │                    │                    │
       ▼                    ▼                    ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Approval    │     │  Tax &       │     │  Banking     │
│  Workflow    │     │  Compliance  │     │  Gateway     │
└──────────────┘     └──────────────┘     └──────────────┘
````

### 3.2 Core Modules

1.  **Employee Master** — Demographics, pay rates, tax elections, certifications.
2.  **Timecard Subsystem** — Daily/weekly time entry, GPS validation, foreman approval.
3.  **Payroll Engine** — Gross-to-net calculation, overtime rules, prevailing wage differentials.
4.  **Tax & Compliance** — Federal/state/local withholding, certified payroll generation.
5.  **Disbursement** — ACH direct deposit, check printing, pay stub delivery.
6.  **Audit & Reporting** — Immutable logs, compliance reports, labor-cost analytics.
7.  **Notification Service** — Alerts for approvals, pay stubs, exceptions.
8.  **Integration Layer** — Accounting, ERP, banking, and government portals.

## 4. Business Rules

### 4.1 Pay Period

*   **Default cycle:** Weekly (Saturday–Friday).
*   **Pay date:** Following Friday, unless it falls on a holiday (then the prior business day).
*   **Cut-off:** Timecards must be submitted by Saturday 23:59 local time and approved by Monday 12:00.

### 4.2 Overtime Calculation

| Rule                              | Threshold     | Rate                           |
| --------------------------------- | ------------- | ------------------------------ |
| Federal FLSA                      | > 40 hrs/week | 1.5× base                      |
| Daily OT (if applicable by state) | > 8 hrs/day   | 1.5× base                      |
| Double time (if applicable)       | > 12 hrs/day  | 2.0× base                      |
| Seventh consecutive day           | All hours     | 1.5× (first 8), 2.0× (after 8) |

### 4.3 Prevailing Wage

*   Prevailing-wage jobs require split tracking: base rate + fringe rate.
*   Fringe obligations may be paid as cash on the check or remitted to approved plans.
*   Certified payroll reports (WH-347) must be generated per project per week.

### 4.4 Deductions

Deductions are processed in priority order:

1.  **Mandatory:** Federal income tax, state income tax, FICA (Social Security + Medicare), local taxes.
2.  **Court-ordered:** Garnishments, child support, tax levies.
3.  **Voluntary:** Union dues, health insurance, retirement contributions, tool allowances.

## 5. Data Model (Summary)

> **Note**  
> Full domain model in **Appendix — Payroll Domain**.

### 5.1 Key Entities

*   **Employee** — Unique record per worker; links to pay rates, tax elections, certifications.
*   **Timecard** — Daily entries with project, cost code, hours, and approval status.
*   **PayRun** — A batch processing event for a given pay period.
*   **PayStub** — Computed output per employee per pay run.
*   **Project** — Job site; carries prevailing-wage and billing metadata.

### 5.2 Relationships

```text
Employee  1──*  Timecard
Employee  1──*  PayStub
PayRun    1──*  PayStub
Project   1──*  Timecard
```

## 6. Functional Requirements

### 6.1 Timecard Entry

| ID    | Requirement                                                                 |
| ----- | --------------------------------------------------------------------------- |
| TC-01 | System shall accept daily time entries with start/end times or total hours. |
| TC-02 | Each entry shall be tied to a Project and Cost Code.                        |
| TC-03 | Foreman shall approve/reject entries before payroll processing.             |
| TC-04 | System shall flag entries exceeding 16 hours in a single day for review.    |
| TC-05 | System shall support bulk entry by foreman for crew-level timekeeping.      |

### 6.2 Payroll Processing

| ID    | Requirement                                                                                    |
| ----- | ---------------------------------------------------------------------------------------------- |
| PR-01 | System shall compute gross pay from approved timecards using the employee's effective rate(s). |
| PR-02 | Overtime shall be calculated per the rules in Section 4.2.                                     |
| PR-03 | Prevailing-wage fringe differential shall be computed and allocated per Section 4.3.           |
| PR-04 | Tax withholding shall be computed using current IRS and state tables.                          |
| PR-05 | Net pay shall be calculated after all deductions in priority order (Section 4.4).              |
| PR-06 | System shall generate a preview pay run for review before finalization.                        |

### 6.3 Disbursement

| ID    | Requirement                                                                        |
| ----- | ---------------------------------------------------------------------------------- |
| DS-01 | System shall generate NACHA-formatted ACH files for direct deposit.                |
| DS-02 | System shall support check printing with MICR encoding.                            |
| DS-03 | Pay stubs shall be delivered electronically (email/portal) and optionally printed. |

### 6.4 Reporting

| ID    | Requirement                                                                    |
| ----- | ------------------------------------------------------------------------------ |
| RP-01 | System shall generate certified payroll reports (WH-347) per project per week. |
| RP-02 | System shall produce quarterly 941 and annual W-2 data.                        |
| RP-03 | System shall provide labor-cost reports by project, cost code, and employee.   |
| RP-04 | System shall produce union remittance reports.                                 |

## 7. Non-Functional Requirements

| Category     | Requirement                                                          |
| ------------ | -------------------------------------------------------------------- |
| Performance  | Pay run for 500 employees shall complete in < 5 minutes.             |
| Availability | 99.9% uptime during business hours (Mon–Fri 06:00–22:00).            |
| Security     | All PII encrypted at rest (AES-256) and in transit (TLS 1.3).        |
| Scalability  | System shall support up to 2,000 active employees.                   |
| Compliance   | SOC 2 Type II; IRS Publication 15 adherence.                         |
| Backup       | Daily automated backups with 90-day retention.                       |
| Audit        | All data mutations logged with user, timestamp, before/after values. |

## 8. User Roles

| Role            | Access Level                                                     |
| --------------- | ---------------------------------------------------------------- |
| Field Worker    | Submit own timecards; view own pay stubs.                        |
| Foreman         | Submit/approve crew timecards; view crew pay summaries.          |
| Project Manager | View project labor costs; approve prevailing-wage allocations.   |
| Payroll Admin   | Full payroll processing; employee master maintenance; reporting. |
| Controller      | Read-only access to all financial data; approve pay runs.        |
| System Admin    | User management; system configuration; integration settings.     |

> **Note**  
> Full permission matrix in **Appendix — Permissions**.

## 9. Processing Workflow

```text
1. Timecard Submission            (Field Worker / Foreman)
2. Timecard Approval              (Foreman)
3. Payroll Preview                (Payroll Admin)
4. Exception Review & Resolution  (Payroll Admin)
5. Pay Run Finalization           (Payroll Admin → Controller Approval)
6. Disbursement                   (ACH / Check Generation)
7. Reporting                      (Certified Payroll, Tax Filings, Union Remittances)
8. Audit Log Sealed
```

## 10. Glossary

| Term              | Definition                                                                        |
| ----------------- | --------------------------------------------------------------------------------- |
| ACH               | Automated Clearing House — electronic funds transfer network.                     |
| Certified Payroll | Weekly payroll report (WH-347) required on federally funded projects.             |
| Cost Code         | Project subdivision for tracking labor costs by work category.                    |
| FICA              | Federal Insurance Contributions Act — Social Security + Medicare taxes.           |
| Fringe            | Non-wage compensation (health, pension, training) required under prevailing wage. |
| Gross Pay         | Total earnings before deductions.                                                 |
| NACHA             | National Automated Clearing House Association — defines ACH file format.          |
| Net Pay           | Take-home pay after all deductions.                                               |
| Prevailing Wage   | Government-mandated minimum wage/fringe for public works projects.                |
| WH-347            | U.S. Department of Labor certified payroll form.                                  |

## 11. Appendices

|  # | Document                  | Description                                                    |
| -: | ------------------------- | -------------------------------------------------------------- |
| 02 | Appendix — Payroll Domain | Entity definitions, relationships, and domain rules.           |
| 03 | Appendix — Timecards      | Timecard subsystem details, validation, and approval workflow. |
| 04 | Appendix — Forecasting    | Labor cost forecasting models and budget projections.          |
| 05 | Appendix — Permissions    | Full RBAC matrix and access control policies.                  |
| 06 | Appendix — Audit          | Audit trail schema, retention, and compliance requirements.    |
| 07 | Appendix — Notifications  | Notification channels, triggers, and templates.                |
| 08 | Appendix — Integrations   | Third-party system interfaces and data exchange formats.       |

**— End of Specification —**

```

If you want, tell me where you’re pasting it (GitHub README, Azure DevOps wiki, Notion, etc.) and I’ll adjust formatting to match that renderer (some handle tables/code blocks slightly differently).
```
