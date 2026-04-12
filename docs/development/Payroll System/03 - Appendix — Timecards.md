# Appendix — Timecards

**Parent Document:** 01 — Payroll System Spec  
**Version:** 1.0  
**Date:** 2026-04-11

---

## 1 Overview

The timecard subsystem captures, validates, and routes daily labor records from field entry through payroll processing. It is the primary data pipeline feeding the payroll engine and must be reliable, auditable, and resistant to data-entry errors.

## 2 Entry Methods

### 2.1 Individual Entry (Field Worker)

1. Worker logs into mobile app or web portal.
2. Selects date, project, and cost code.
3. Enters start/end times or total hours.
4. Adds optional notes.
5. Submits for foreman approval.

### 2.2 Crew Entry (Foreman)

6. Foreman selects crew roster (predefined or ad hoc).
7. Enters single date, project, and cost code.
8. Applies hours to all crew members simultaneously.
9. Can override individual entries as needed.
10. Bulk submission triggers individual timecard records per employee.

### 2.3 Import Entry

CSV or API import for third-party time clocks.

**Required columns:**

| Column            | Description                  |
|------------------|------------------------------|
| `employee_number`| Unique employee identifier   |
| `work_date`      | Date of work performed       |
| `project_number` | Project identifier           |
| `cost_code`      | Cost code for the work activity |
| `hours`          | Total hours worked           |

The system validates all references before creating records. Invalid rows are rejected and reported; valid rows are created as draft.

## 3 Validation Rules

| Rule ID | Rule | Severity |
|--------|------|----------|
| V-01 | Employee must have `status=active` | **Block** |
| V-02 | Project must have `status=active` | **Block** |
| V-03 | Cost code must belong to specified project | **Block** |
| V-04 | `work_date` must not be in the future | **Block** |
| V-05 | `work_date` must be within current or prior pay period | **Block** |
| V-06 | Total hours for single day must not exceed 24 | **Block** |
| V-07 | Total hours for single day should not exceed 16 | Warning |
| V-08 | Duplicate entry for same employee + date + project + cost code | Warning |
| V-09 | Employee has no approved rate for specified project | Warning |
| V-10 | Entry submitted after pay period cut-off | Warning (requires admin override) |

**Severity Definitions**
- **Block** — Entry cannot be saved until the condition is resolved.
- **Warning** — Entry can be saved but is flagged for review.

## 4 Overtime Auto-Calculation

### 4.1 Weekly Overtime (Default)

```text
FOR each employee IN pay_period:
    running_total = 0
    FOR each day IN pay_period (sorted by date):
        running_total += day.hours
        IF running_total > 40:
            day.overtime_hours = running_total - 40
            day.regular_hours  = day.hours - day.overtime_hours
        ELSE:
            day.regular_hours  = day.hours
            day.overtime_hours = 0
````

### 4.2 Daily Overtime (California)

```text
FOR each day:
    IF day.hours > 12:
        day.regular_hours    = 8
        day.overtime_hours   = 4
        day.doubletime_hours = day.hours - 12
    ELSE IF day.hours > 8:
        day.regular_hours    = 8
        day.overtime_hours   = day.hours - 8
        day.doubletime_hours = 0
    ELSE:
        day.regular_hours    = day.hours
        day.overtime_hours   = 0
        day.doubletime_hours = 0
```

### 4.3 Seventh-Day Rule

If an employee has worked 6 consecutive days in the workweek, on the 7th day:

*   First 8 hours are paid at **1.5×** the regular rate.
*   Hours beyond 8 are paid at **2.0×** the regular rate.

### 4.4 Override

Payroll Admin may manually override computed overtime classifications with documented justification. All overrides are logged with the original computed values preserved.

## 5 Approval Workflow

### 5.1 Flow Diagram

```text
+---------+      +-----------+      +----------+      +-----------+
|  DRAFT  |----->| SUBMITTED |----->| APPROVED |----->| PROCESSED |
+---------+      +-----------+      +----------+      +-----------+
      ^                                   |
      |                                   v
      |                              +-----------+
      +------------------------------| REJECTED  |
                                     +-----------+
```

### 5.2 Approval Rules

| Rule                  | Description                                                  |
| --------------------- | ------------------------------------------------------------ |
| Self-approval         | Not allowed. An employee may not approve their own timecard. |
| Foreman scope         | Limited to assigned crew and project only.                   |
| Batch approval        | Available. Foreman may approve multiple timecards at once.   |
| Rejection             | Returns timecard to draft status; a reason is required.      |
| Unapproved at cut-off | Escalated to Payroll Admin for resolution.                   |
| Late entries          | Require Payroll Admin approval.                              |

### 5.3 Approval Notifications

| Event                        | Notification Target      | Channel                    |
| ---------------------------- | ------------------------ | -------------------------- |
| Timecard submitted           | Foreman                  | Push + Email               |
| Timecard approved            | Employee                 | Push                       |
| Timecard rejected            | Employee                 | Push + Email (with reason) |
| Cut-off approaching (24 hrs) | All with draft timecards | Push + Email               |
| Unapproved at cut-off        | Payroll Admin            | Email (escalation)         |

## 6 Prevailing-Wage Timecards

For projects flagged `is_prevailing_wage=true`, the following additional fields are required:

| Field                    | Description                                          |
| ------------------------ | ---------------------------------------------------- |
| `work_classification`    | DOL-recognized classification for the work performed |
| `prevailing_base_rate`   | Prevailing wage base rate for the classification     |
| `prevailing_fringe_rate` | Prevailing wage fringe benefit rate                  |
| `fringe_payment_method`  | `cash` or `plan`                                     |

**Business Rules:**

*   Classification must match the applicable wage determination.
*   Multiple classifications performed in a single day require separate timecard entries.
*   Fringe benefit tracking is mandatory for WH-347 certified payroll reporting.

## 7 Corrections and Adjustments

### 7.1 Pre-Processing Corrections

Timecards in **draft**, **submitted**, or **approved** status may be edited by authorized users.

*   Editing a **submitted** timecard returns it to **draft** status.
*   Editing an **approved** timecard requires Payroll Admin authorization.

### 7.2 Post-Processing Corrections

Processed timecards **cannot** be directly modified. Corrections are made via adjustment entries:

11. A **negative reversal** entry offsets the original values.
12. A **positive correction** entry records the correct values.
13. Both entries are linked to the original timecard record.
14. Adjustment entries are included in the next pay run.

## 8 Reporting

### 8.1 Timecard Reports

| Report                 | Primary Audience        |
| ---------------------- | ----------------------- |
| Daily Time Summary     | Foreman                 |
| Weekly Time Summary    | Foreman / Payroll Admin |
| Missing Timecards      | Foreman / Payroll Admin |
| Exception Report       | Payroll Admin           |
| Late Submission Report | Payroll Admin           |
| Hours by Cost Code     | Project Manager         |

### 8.2 Export Formats

*   PDF
*   CSV
*   JSON

***

*End of Appendix — Timecards*

