# Appendix — Integrations

**Parent Document:** 01 — Payroll System Spec  
**Version:** 1.0  
**Date:** 2026-04-11  

## 1 Overview

The integration layer connects the payroll system to external services for accounting, tax compliance, banking, time clocks, HR platforms, and government reporting portals. All integrations follow a standardized pattern for authentication, data exchange, error handling, and monitoring.

## 2 Integration Architecture

### 2.1 Design Principles

| Principle | Description |
|---|---|
| **Loose Coupling** | Message-based exchange; no direct DB connections |
| **Idempotency** | All operations safely retried without duplicate effects |
| **Fail-safe** | Integration failures do not block core payroll processing |
| **Audit Trail** | All inbound and outbound data exchanges logged |
| **Configuration-driven** | Endpoints, credentials, mappings managed via admin UI; no code changes |

### 2.2 Integration Patterns

```

┌─────────────────────┐                         │                         │
│      Payroll System  │
└──┬───┬───┬───┬──────┘
│   │   │   │
│   │   │   └────────────┐
│   │   └────────────┐   │
│   └────────────┐   │   │
└────────────┐   │   │   │
REST API  Webhook  File (SFTP)  Batch Job
┌──▼───▼───▼───▼──────┐
│   External Service  │
└─────────────────────┘

````

| Pattern | Description |
|---|---|
| **REST API — Outbound** | Real-time data push to accounting, HR |
| **REST API — Inbound** | Time clock data ingestion, rate updates |
| **Webhook** | Event-driven notifications from external services |
| **File Transfer (SFTP)** | ACH files to banking, certified payroll to portals |
| **Batch Job** | Scheduled data sync — nightly GL export, weekly tax filing |

## 3 Integration Catalog

### 3.1 Accounting / ERP

| Integration | Direction | Pattern | Data Exchanged | Frequency |
|---|---|---|---|---|
| QuickBooks Online | Outbound | REST API | GL journal entries, vendor payments | Per pay run |
| Sage 300 CRE | Outbound | File (CSV) | Job cost entries, labor distribution | Per pay run |
| Viewpoint Vista | Bidirectional | REST API | Job cost (out), project-cost code sync (in) | Per pay run (out) / Daily (in) |
| Foundation Software | Outbound | File (CSV) | Labor cost entries | Per pay run |
| Generic GL Export | Outbound | File (CSV/JSON) | Configurable GL mapping | Per pay run |

**3.1.1 GL Journal Entry Format**

```json
{
  "journal_entry": {
    "date": "2026-04-11",
    "reference": "PR-2026-015",
    "description": "Payroll journal entry — Pay Run PR-2026-015",
    "lines": [
      { "account": "5100-001", "description": "Direct labor", "debit": 45230.00, "credit": 0.00 },
      { "account": "2100-001", "description": "Federal tax payable", "debit": 0.00, "credit": 6321.50 },
      { "account": "2100-002", "description": "State tax payable", "debit": 0.00, "credit": 2845.60 },
      { "account": "1000-001", "description": "Payroll clearing", "debit": 0.00, "credit": 36062.90 }
    ]
  }
}
````

**3.1.2 GL Account Mapping**

| Payroll Category         | Default GL Account | Configurable                  |
| ------------------------ | ------------------ | ----------------------------- |
| Direct labor — regular   | 5100-001           | Yes — per project / cost code |
| Direct labor — OT        | 5100-002           | Yes                           |
| Direct labor — DT        | 5100-003           | Yes                           |
| Fringe — cash            | 5200-001           | Yes                           |
| Fringe — plan remittance | 5200-002           | Yes                           |
| Federal tax payable      | 2100-001           | Yes                           |
| State tax payable        | 2100-002           | Yes                           |
| FICA — employee          | 2100-003           | Yes                           |
| FICA — employer          | 2100-004           | Yes                           |
| Net pay clearing         | 1000-001           | Yes                           |
| Garnishments payable     | 2300-001           | Yes                           |
| Union dues payable       | 2400-001           | Yes                           |

### 3.2 Banking / ACH

| Integration         | Direction | Pattern         | Data Exchanged                      | Frequency                      |
| ------------------- | --------- | --------------- | ----------------------------------- | ------------------------------ |
| ACH Direct Deposit  | Outbound  | File (SFTP)     | NACHA-formatted ACH file            | Per pay run                    |
| Positive Pay        | Outbound  | File (SFTP)     | Check register for fraud prevention | Per pay run (if checks issued) |
| Bank Reconciliation | Inbound   | File (SFTP/API) | Cleared transactions                | Daily                          |

**3.2.1 NACHA File Structure**

    File Header Record (1 record)
    └─ Batch Header Record (1 per company)
       └─ Entry Detail Record (1 per employee)
          └─ Addenda Record (optional — pay stub reference)
       └─ Batch Control Record
    └─ File Control Record

**3.2.2 ACH Processing Timeline**

| Day                 | Activity                                                       |
| ------------------- | -------------------------------------------------------------- |
| **Day 0 — Friday**  | Pay run finalized; ACH file generated and transmitted via SFTP |
| **Day 1 — Monday**  | Bank processes origination                                     |
| **Day 2 — Tuesday** | Settlement — funds available                                   |

**ACH Configuration**

| Setting            | Description                     | Scope              |
| ------------------ | ------------------------------- | ------------------ |
| `originating_dfi`  | Bank routing number             | Per company        |
| `company_id`       | EIN                             | Per company        |
| `sftp_host`        | Bank SFTP endpoint              | Per bank           |
| `sftp_credentials` | Username + SSH key              | Encrypted in vault |
| `file_naming`      | `ACH_[YYYYMMDD]_[sequence].txt` | —                  |

### 3.3 Tax Services

| Integration        | Direction | Pattern         | Data Exchanged                   | Frequency               |
| ------------------ | --------- | --------------- | -------------------------------- | ----------------------- |
| Federal IRS EFTPS  | Outbound  | File / Portal   | Tax deposits (941)               | Per pay run / Quarterly |
| State Tax Portals  | Outbound  | File / Portal   | State withholding deposits       | Per pay run / Quarterly |
| W-2 / 1099 Service | Outbound  | REST API / File | Annual wage and tax statements   | Annually (January)      |
| Tax Table Provider | Inbound   | REST API        | Updated federal/state tax tables | As published            |

**3.3.1 Tax Filing Calendar**

| Filing                    | Frequency   | Deadline                                |
| ------------------------- | ----------- | --------------------------------------- |
| Federal 941 deposit       | Per pay run | By next Wednesday (semi-weekly)         |
| Federal 941 return        | Quarterly   | Last day of month following quarter end |
| State withholding deposit | Per pay run | Varies by state                         |
| State withholding return  | Quarterly   | Varies                                  |
| W-2 employee copies       | Annually    | January 31                              |
| W-2 SSA filing            | Annually    | January 31                              |
| W-3 transmittal           | Annually    | January 31                              |

**3.3.2 Tax Table Update Flow**

1.  Provider publishes update
2.  System receives via API webhook
3.  Payroll Admin notified (SY-N07)
4.  Payroll Admin reviews and approves
5.  Updated tables take effect next pay run
6.  Change logged in audit trail

### 3.4 Time Clocks / Field Devices

| Integration          | Direction | Pattern     | Data Exchanged                | Frequency |
| -------------------- | --------- | ----------- | ----------------------------- | --------- |
| Biometric Time Clock | Inbound   | REST API    | Punch in/out events           | Real-time |
| GPS Tracking App     | Inbound   | REST API    | Location-stamped clock events | Real-time |
| CSV Time Import      | Inbound   | File upload | Bulk time entries             | On demand |

**3.4.1 Time Clock Data Format**

```json
{
  "employee_number": "EMR-0042",
  "event_type": "clock_in",
  "timestamp": "2026-04-11T06:58:32-04:00",
  "source_device": "CLOCK-SITE-A",
  "gps_latitude": 42.0398,
  "gps_longitude": -71.1860,
  "project_number": "PRJ-2026-042"
}
```

**3.4.2 Time Clock Mapping Rules**

| Scenario                          | Action                                      |
| --------------------------------- | ------------------------------------------- |
| `clock_in` + `clock_out` same day | Create timecard; calculate total hours      |
| `clock_in` without `clock_out`    | Flag exception; notify Foreman              |
| Multiple pairs                    | Calculate total with breaks excluded        |
| GPS outside geofence              | Flag for Foreman review; do not auto-reject |

### 3.5 Government Reporting

| Integration                   | Direction | Pattern         | Data Exchanged                           | Frequency          |
| ----------------------------- | --------- | --------------- | ---------------------------------------- | ------------------ |
| LCPtracker                    | Outbound  | REST API / File | Certified payroll (WH-347)               | Weekly per project |
| Elation Systems               | Outbound  | REST API        | Certified payroll                        | Weekly per project |
| State Prevailing Wage Portals | Outbound  | File (PDF/CSV)  | State-specific certified payroll         | Weekly per project |
| EEO-1 Reporting               | Outbound  | File (CSV)      | Workforce demographics                   | Annually           |
| OSHA 300 Log                  | Outbound  | File (PDF)      | Hours worked for injury rate calculation | Annually           |

**3.5.1 WH-347 Certified Payroll Data**

| Field                     | Source                                 |
| ------------------------- | -------------------------------------- |
| Contractor name / address | Company master                         |
| Payroll number            | Sequential per project per week        |
| Week ending date          | PayRun.pay\_period\_end                |
| Employee name             | Employee first + last                  |
| Work classification       | Timecard.work\_classification          |
| Hours worked (ST/OT)      | Timecard hours aggregated by day       |
| Rate of pay               | PayRate prevailing base + fringe       |
| Gross pay                 | PayStub.gross\_pay (project-allocated) |
| Deductions                | PayStub deductions (project-allocated) |
| Net pay                   | PayStub.net\_pay (project-allocated)   |

### 3.6 HR / Benefits

| Integration      | Direction     | Pattern        | Data Exchanged                                | Frequency   |
| ---------------- | ------------- | -------------- | --------------------------------------------- | ----------- |
| BambooHR         | Bidirectional | REST API       | Employee demographics (in), pay changes (out) | Daily       |
| Benefits Carrier | Outbound      | File (EDI 834) | Enrollment and deduction data                 | Per pay run |
| Union Trust Fund | Outbound      | File (CSV/API) | Hours worked, fringe contributions            | Monthly     |
| 401(k) Provider  | Outbound      | File (CSV)     | Employee contributions, match amounts         | Per pay run |

## 4 Authentication and Security

### 4.1 Authentication Methods

| Method                         | Use Case                                       |
| ------------------------------ | ---------------------------------------------- |
| OAuth 2.0 — Client Credentials | Service-to-service APIs (QuickBooks, BambooHR) |
| OAuth 2.0 — Authorization Code | User-initiated connections for initial setup   |
| API Key + Secret               | Simple integrations (time clocks)              |
| Mutual TLS (mTLS)              | Banking connections (ACH, positive pay)        |
| SSH Key                        | SFTP file transfers                            |
| HMAC Signature                 | Webhook payload verification                   |

### 4.2 Credential Management

| Aspect       | Policy                                                                                |
| ------------ | ------------------------------------------------------------------------------------- |
| **Storage**  | All credentials encrypted in dedicated vault (HashiCorp Vault or AWS Secrets Manager) |
| **Rotation** | API keys every 90 days; SSH keys every 180 days                                       |
| **Access**   | Only System Admin can view/modify integration credentials                             |
| **Audit**    | All credential access and changes logged                                              |

### 4.3 Network Security

| Control             | Description                                                 |
| ------------------- | ----------------------------------------------------------- |
| **IP Whitelisting** | Inbound API calls restricted to known partner IPs           |
| **TLS**             | All API traffic TLS 1.3 minimum                             |
| **VPN**             | Banking SFTP via site-to-site VPN where required            |
| **WAF**             | Web Application Firewall on all public-facing API endpoints |

## 5 Error Handling

### 5.1 Error Categories

| Category           | Examples                                                        | Response                            |
| ------------------ | --------------------------------------------------------------- | ----------------------------------- |
| **Transient**      | Network timeout, rate limit, temporary unavailability           | Retry with exponential backoff      |
| **Validation**     | Data format error, missing field, invalid reference             | Log and queue for manual review     |
| **Authentication** | Expired token, invalid credentials, cert mismatch               | Alert SysAdmin; pause integration   |
| **Business**       | Duplicate transaction, rejected record, business rule violation | Log and queue for manual review     |
| **Fatal**          | Service permanently unavailable, contract terminated            | Alert SysAdmin; disable integration |

### 5.2 Retry Policy

| Attempt              | Delay       |
| -------------------- | ----------- |
| 1st retry            | 30 seconds  |
| 2nd retry            | 2 minutes   |
| 3rd retry            | 10 minutes  |
| 4th retry            | 1 hour      |
| Final retry          | 4 hours     |
| **Maximum attempts** | **5 total** |

After all retries exhausted: log failure, alert SysAdmin, queue for manual processing.

### 5.3 Dead Letter Queue

*   Failed messages routed to DLQ
*   DLQ entries include: original payload, error details, retry count, timestamp
*   SysAdmin can inspect, re-process, or discard
*   Entries older than 30 days trigger escalation alert

## 6 Monitoring

### 6.1 Health Checks

| Target             | Method                              |     Interval | Timeout |
| ------------------ | ----------------------------------- | -----------: | ------: |
| REST API endpoints | `GET /health` or `/ping`            |  Every 5 min |  10 sec |
| SFTP connections   | Test connection + directory listing | Every 15 min |  30 sec |
| Webhook receivers  | Synthetic test event                | Every 30 min |  15 sec |

### 6.2 Metrics

| Metric                | Measurement                         | Alert Threshold         |
| --------------------- | ----------------------------------- | ----------------------- |
| API response time     | Average per integration             | > 5 sec                 |
| Error rate            | % failed requests                   | > 5%                    |
| Queue depth           | Pending outbound messages           | > 100                   |
| DLQ depth             | Failed messages                     | > 10                    |
| Sync lag              | Time since last successful exchange | > 2× expected frequency |
| File transfer success | SFTP completion rate                | < 99%                   |

### 6.3 Dashboard

*   Real-time status (green / yellow / red) per integration
*   Last successful sync timestamp
*   Error count — 24h / 7d / 30d
*   DLQ depth and oldest entry age
*   Upcoming scheduled sync jobs

## 7 Configuration

### 7.1 Admin Interface

| Setting            | Description                                              |
| ------------------ | -------------------------------------------------------- |
| **Enabled**        | Toggle on/off                                            |
| **Endpoint URL**   | Target service URL or SFTP host                          |
| **Authentication** | Method and credentials (stored in vault)                 |
| **Field Mapping**  | Map payroll fields to external system fields             |
| **Schedule**       | Sync frequency: real-time / per pay run / daily / weekly |
| **Retry Policy**   | Override default retry settings                          |
| **Notification**   | Who receives sync success/failure alerts                 |
| **Test Mode**      | Send to sandbox/test environment instead of production   |

**7.2 Field Mapping Example (GL Export)**

```json
{
  "mappings": [
    {
      "source": "PayStub.gross_pay",
      "target": "JournalLine.debit",
      "account": "5100-001",
      "transform": "none"
    },
    {
      "source": "PayStub.federal_tax",
      "target": "JournalLine.credit",
      "account": "2100-001",
      "transform": "none"
    },
    {
      "source": "Timecard.project_number",
      "target": "JournalLine.project",
      "transform": "prefix:JOB-"
    }
  ]
}
```

## 8 Data Exchange Standards

| Standard       | Usage                     | Reference                                  |
| -------------- | ------------------------- | ------------------------------------------ |
| NACHA          | ACH direct deposit files  | NACHA Operating Rules (current year)       |
| EDI 834        | Benefits enrollment       | ASC X12N 834                               |
| WH-347         | Certified payroll         | U.S. DOL form specification                |
| JSON:API       | RESTful data exchange     | jsonapi.org specification                  |
| CSV (RFC 4180) | Flat file imports/exports | RFC 4180 compliant                         |
| ISO 8601       | Date/time formatting      | All timestamps in UTC with timezone offset |
| ISO 4217       | Currency codes            | USD for all monetary values                |

***

*End of Appendix — Integrations*

```
```
