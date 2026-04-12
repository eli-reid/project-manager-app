# Appendix — Audit

Payroll System — Technical Appendix

| Parent Document: | 01 — Payroll System Spec |
|---|---|
| Version: | 1.0 |
| Date: | 2026-04-11 |

## 1 Overview

The audit subsystem provides a tamper-evident, immutable record of every data mutation, access event, and system action within the payroll system. It is designed to satisfy IRS record-keeping requirements, SOC 2 controls, Davis-Bacon Act compliance, and internal governance policies.

## 2 Audit Scope

### 2.1 What Is Logged

| Category | Examples |
|---|---|
| **Data Mutations** | Employee record changes, rate updates, timecard edits, deduction modifications |
| **Workflow Events** | Timecard submissions, approvals, rejections; pay run creation, preview, approval, finalization |
| **Access Events** | Login/logout, failed login attempts, SSN views, report generation, data exports |
| **System Events** | Tax table updates, integration sync, configuration changes, user role modifications |
| **Financial Events** | ACH file generation, check printing, pay stub delivery, garnishment processing |

### 2.2 What Is NOT Logged

| Excluded Item | Reason |
|---|---|
| Read-only page views (non-sensitive) | Volume reduction — sensitive reads are logged |
| Search queries (non-PII) | Volume reduction |
| Session keep-alive pings | No business value |

## 3 Audit Record Schema

### 3.1 Core Fields

| Field | Type | Description |
|---|---|---|
| `audit_id` | UUID | Immutable primary key |
| `timestamp` | TIMESTAMP(6) | UTC with microsecond precision |
| `event_type` | ENUM | `create` \| `update` \| `delete` \| `access` \| `approve` \| `reject` \| `login` \| `logout` \| `export` \| `system` |
| `entity_type` | VARCHAR(50) | Type of entity affected |
| `entity_id` | UUID | Identifier of affected entity |
| `user_id` | UUID | FK to User; `SYSTEM` for automated actions |
| `user_role` | VARCHAR(30) | Role of acting user at time of event |
| `ip_address` | VARCHAR(45) | IPv4 or IPv6 |
| `user_agent` | VARCHAR(200) | Client user agent string |
| `action_summary` | VARCHAR(200) | Human-readable description of action |

### 3.2 Change Detail Fields

| Field | Type | Description |
|---|---|---|
| `field_name` | VARCHAR(50) | Name of the changed field |
| `old_value` | TEXT | Previous value (encrypted if PII) |
| `new_value` | TEXT | New value (encrypted if PII) |

Multiple field changes produce multiple detail records linked by the same `audit_id`.

### 3.3 Example Record

```json
{
  "audit_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "timestamp": "2026-04-11T14:32:07.482613Z",
  "event_type": "update",
  "entity_type": "Employee",
  "entity_id": "EMR-0042",
  "user_id": "USR-0017",
  "user_role": "PayrollAdmin",
  "ip_address": "10.0.1.42",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
  "action_summary": "Updated pay rate for Employee EMR-0042",
  "changes": [
    {
      "field_name": "default_rate",
      "old_value": "32.5000",
      "new_value": "34.7500"
    }
  ]
}
````

## 4 Immutability

### 4.1 Write-Once Architecture

*   Append-only — no update or delete permitted
*   Database user has `INSERT` and `SELECT` only
*   Application enforces write-once semantics

### 4.2 Tamper Detection

| Mechanism           | Description                                                                                      |
| ------------------- | ------------------------------------------------------------------------------------------------ |
| **Hash Chain**      | Each record includes a SHA-256 hash of the prior record                                          |
| **Daily Digest**    | Daily digest hash computed and stored in air-gapped archive                                      |
| **Integrity Check** | Nightly job validates hash chain; a break triggers an immediate alert to SysAdmin and Controller |

### 4.3 Hash Chain Formula

```text
record_hash = SHA-256(
    audit_id  + timestamp  + event_type  + entity_type  + entity_id  +
    user_id   + action_summary + change_details_json + previous_record_hash
)
```

## 5 Sensitive Data Handling

### 5.1 PII in Audit Logs

| Data Element  | Storage Rule                               |
| ------------- | ------------------------------------------ |
| SSN           | Last 4 only                                |
| Bank account  | Last 4 only                                |
| Pay rates     | Full value stored                          |
| Tax elections | Full value stored                          |
| Passwords     | Not stored — only event "password changed" |

### 5.2 Encryption

*   Change detail values containing PII are encrypted at rest using AES-256.
*   Decryption requires **Auditor** or **Controller** role + MFA.
*   Encryption keys are rotated annually via a dedicated KMS.

## 6 Audit Reports

### 6.1 Standard Reports

| Report                   | Description                              | Audience             | Frequency   |
| ------------------------ | ---------------------------------------- | -------------------- | ----------- |
| Change Log               | All data mutations                       | Controller, Auditor  | On demand   |
| Access Log               | All access events                        | SysAdmin, Auditor    | On demand   |
| Payroll Processing Trail | Full pay run lifecycle                   | Controller, Auditor  | Per pay run |
| Employee History         | Complete change history for one employee | PayAdmin, Auditor    | On demand   |
| Exception Report         | Anomalous events                         | SysAdmin, Controller | Daily       |
| Integrity Report         | Hash chain validation                    | SysAdmin             | Daily       |
| User Activity Summary    | Actions by specific user                 | SysAdmin, Auditor    | On demand   |

### 6.2 Report Filters

| Filter      | Required |
| ----------- | -------- |
| Date Range  | Yes      |
| Event Type  | No       |
| Entity Type | No       |
| User        | No       |
| Role        | No       |
| Entity ID   | No       |

### 6.3 Export Formats

| Format   | Description                                                |
| -------- | ---------------------------------------------------------- |
| **PDF**  | Formatted, signed, timestamped — for regulatory submission |
| **CSV**  | Raw data — for external analysis                           |
| **JSON** | API-compatible — for SIEM integration                      |

## 7 Retention and Archival

### 7.1 Retention Periods

| Record Type                  |  Active | Archive |   Total |
| ---------------------------- | ------: | ------: | ------: |
| Payroll processing events    | 2 years | 5 years | 7 years |
| Employee data changes        | 2 years | 5 years | 7 years |
| Timecard changes             | 2 years | 5 years | 7 years |
| Access events (login/logout) |  1 year | 2 years | 3 years |
| System configuration changes | 2 years | 5 years | 7 years |
| Integrity check results      |  1 year | 6 years | 7 years |

### 7.2 Archival Process

1.  Mark records for archival based on retention schedule.
2.  Compress and encrypt archived records.
3.  Store in a separate read-only storage tier.
4.  Verify quarterly via hash chain validation.
5.  Archived records remain searchable.
6.  Permanently destroy after total retention period expires.

### 7.3 Legal Hold

*   When a legal hold is active, no records in the hold scope may be archived or destroyed.
*   Legal holds are created by the **Controller** or **SysAdmin**.
*   Scope is defined by entity type, date range, and/or employee.
*   A hold remains in effect until explicitly released.

## 8 Anomaly Detection

### 8.1 Automated Alerts

| Anomaly                      | Detection Rule                                           | Alert Target          |
| ---------------------------- | -------------------------------------------------------- | --------------------- |
| Mass data change             | >50 records modified by one user in 10 minutes           | SysAdmin              |
| After-hours access           | Login or sensitive action outside 06:00–22:00            | SysAdmin              |
| Failed login spike           | >3 failed attempts for a single account in 5 minutes     | SysAdmin              |
| SSN access burst             | >5 SSN views by one user in 1 hour                       | Controller            |
| Unauthorized role access     | API call without required permission                     | SysAdmin              |
| Rate change without approval | PayRate modified without a corresponding approval record | Controller            |
| Hash chain break             | Nightly integrity check fails                            | SysAdmin + Controller |

### 8.2 Alert Channels

| Severity     | Channels                       |
| ------------ | ------------------------------ |
| **Critical** | SMS + Email + Dashboard banner |
| **High**     | Email + Dashboard notification |
| **Medium**   | Dashboard notification         |
| **Low**      | Dashboard only                 |

## 9 Compliance Mapping

| Requirement                                    | Standard     | Audit Implementation                      |
| ---------------------------------------------- | ------------ | ----------------------------------------- |
| Record all payroll transactions for 4+ years   | IRS Pub 15   | 7-year retention                          |
| Track FTI access                               | IRS Pub 1075 | Access events logged                      |
| Maintain certified payroll records for 3 years | Davis-Bacon  | 7-year retention                          |
| Log all financial data changes                 | SOC 2 CC6.1  | All mutations with before/after values    |
| Restrict and monitor sensitive data access     | SOC 2 CC6.3  | Access events + anomaly detection         |
| Demonstrate data integrity                     | SOC 2 CC7.1  | Hash chain + daily digest + nightly check |

End of Appendix — Audit