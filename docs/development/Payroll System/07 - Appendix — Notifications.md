# Appendix — Notifications

Parent Document: 01 — Payroll System Spec | Version 1.0 | Date: 2026-04-11

---

## 1 Overview

The notification service delivers time-sensitive alerts, reminders, and confirmations to system users through multiple channels. It ensures that payroll workflows proceed on schedule by informing stakeholders of pending actions, completed events, and exceptions that require attention.

## 2 Notification Channels

### 2.1 Available Channels

| Channel | Description | Delivery | Use Case |
|---|---|---|---|
| **Push** | Mobile/web push | Instant | Real-time alerts for field users |
| **Email** | Formatted email | Near-instant (<2 min) | Detailed notifications with context |
| **SMS** | Text message | Instant | Critical alerts when app/email unavailable |
| **In-App** | Banner or badge | On next app visit | Non-urgent informational |
| **Dashboard** | Persistent widget on home screen | On next login/refresh | Aggregated status and action items |

### 2.2 Channel Selection

| Rule | Description |
|---|---|
| **Mandatory channels** | Some notifications cannot be disabled (e.g., pay stub delivery, security alerts) |
| **Fallback** | If primary fails, retry via next available channel |
| **Quiet hours** | Push and SMS suppressed 22:00–06:00 (default) |
| **Quiet hours override** | Critical/emergency notifications bypass quiet hours |

### 2.3 Channel Priority (Fallback Order)

1. **Push**
2. **Email**
3. **SMS** (critical only)
4. **In-App** (always delivered as backup)

## 3 Notification Catalog

### 3.1 Timecard Notifications

| ID | Event | Recipients | Channels | Priority |
|---|---|---|---|---|
| TC-N01 | Timecard submitted by employee | Foreman | Push + Email | Normal |
| TC-N02 | Timecard submitted by foreman (crew) | Each crew member | Push | Low |
| TC-N03 | Timecard approved | Employee | Push | Low |
| TC-N04 | Timecard rejected | Employee | Push + Email | **High** |
| TC-N05 | Cut-off reminder (24 hrs) | Employees with draft/missing | Push + Email | Normal |
| TC-N06 | Cut-off reminder (4 hrs) | Employees with draft/missing | Push + Email + SMS | **High** |
| TC-N07 | Unapproved at cut-off | Payroll Admin | Email | **High** |
| TC-N08 | Late timecard submitted | Payroll Admin | Email | Normal |
| TC-N09 | Timecard adjustment created | Employee + Payroll Admin | Email | Normal |

### 3.2 Payroll Processing Notifications

| ID | Event | Recipients | Channels | Priority |
|---|---|---|---|---|
| PR-N01 | Pay run created (preview ready) | Controller | Email | Normal |
| PR-N02 | Pay run exceptions found | Payroll Admin | Email + Push | **High** |
| PR-N03 | Pay run approved by Controller | Payroll Admin | Email | Normal |
| PR-N04 | Pay run finalized | Controller + Payroll Admin | Email | Normal |
| PR-N05 | Pay run voided | Controller + System Admin | Email + SMS | **Critical** |
| PR-N06 | ACH file generated | Controller | Email | Normal |
| PR-N07 | Direct deposit scheduled | All employees with DD | Push + Email | Normal |
| PR-N08 | Pay stub available | Employee | Push + Email | Normal |

### 3.3 Employee Notifications

| ID | Event | Recipients | Channels | Priority |
|---|---|---|---|---|
| EM-N01 | Rate change effective | Employee | Email | Normal |
| EM-N02 | Deduction added/modified | Employee | Email | Normal |
| EM-N03 | W-4 update confirmation | Employee | Email | Normal |
| EM-N04 | Direct deposit update confirmation | Employee | Email + Push | Normal |
| EM-N05 | Certification expiring (30 days) | Employee + Foreman | Email + Push | Normal |
| EM-N06 | Certification expired | Employee + Foreman + PM | Email | **High** |

### 3.4 System and Security Notifications

| ID | Event | Recipients | Channels | Priority |
|---|---|---|---|---|
| SY-N01 | Failed login attempt (3+) | Account owner + System Admin | Email | **High** |
| SY-N02 | Account locked | Account owner + System Admin | Email + SMS | **Critical** |
| SY-N03 | Password changed | Account owner | Email | Normal |
| SY-N04 | MFA enrolled/changed | Account owner | Email | Normal |
| SY-N05 | Role assignment changed | Affected user + System Admin | Email | Normal |
| SY-N06 | Integration sync failure | System Admin | Email + SMS | **Critical** |
| SY-N07 | Tax table update available | Payroll Admin | Email | Normal |
| SY-N08 | Hash chain integrity failure | System Admin + Controller | Email + SMS | **Critical** |

### 3.5 Compliance Notifications

| ID | Event | Recipients | Channels | Priority |
|---|---|---|---|---|
| CO-N01 | Certified payroll due reminder | PM + Payroll Admin | Email + Push | Normal |
| CO-N02 | Certified payroll generated | PM | Email | Normal |
| CO-N03 | Quarterly tax filing due (30 days) | Controller + Payroll Admin | Email | Normal |
| CO-N04 | Annual W-2 generation complete | Payroll Admin | Email | Normal |
| CO-N05 | Audit log retention action | System Admin | Email | Low |

## 4 Notification Templates

### 4.1 Template Structure


┌─────────────────────────────────────────┐
│  Subject Line                           │
├─────────────────────────────────────────┤
│  Greeting (personalized)                │
├─────────────────────────────────────────┤
│  Body                                   │
│    • Event description                  │
│    • Context / details                  │
├─────────────────────────────────────────┤
│  Action Button (if applicable)          │
├─────────────────────────────────────────┤
│  Footer                                 │
│    • System info                        │
│    • Unsubscribe                        │
└─────────────────────────────────────────┘

```

### 4.2 Example Templates

**TC-N04 — Timecard Rejected (Email)**

```

Subject:  Timecard Rejected — \[work\_date]

Hi \[first\_name],

Your timecard for \[project\_name] on \[work\_date] has been rejected by \[foreman\_name].
Reason: \[rejection\_reason]

Please review and resubmit before the cut-off deadline.

┌──────────────────┐
│  Review Timecard  │
└──────────────────┘

— Payroll System Notification
To manage notification preferences, visit Settings.

```

**PR-N08 — Pay Stub Available (Push)**

```

Title:   Pay Stub Ready
Body:    Your pay stub for \[pay\_period\_end] is now available. Tap to view.
Action:  Open Pay Stub

```

**SY-N08 — Hash Chain Integrity Failure (SMS)**

```

CRITICAL: Audit hash chain integrity failure detected at \[timestamp]. Immediate investigation required. Log into the admin console.

```

### 4.3 Template Variables

| Variable | Source | Example |
|---|---|---|
| `[first_name]` | Employee.first_name | Eli |
| `[work_date]` | Timecard.work_date | 2026-04-07 |
| `[project_name]` | Project.project_name | City Hall Renovation |
| `[project_number]` | Project.project_number | PRJ-2026-042 |
| `[foreman_name]` | User.display_name | Mike Torres |
| `[rejection_reason]` | Timecard.rejection_notes | "Hours exceed schedule — verify with PM." |
| `[cutoff_date]` | PayPeriod.cutoff_date | 2026-04-12 |
| `[cutoff_time]` | System.cutoff_time | 23:59 EDT |
| `[pay_period_end]` | PayRun.pay_period_end | 2026-04-11 |
| `[timestamp]` | System.current_time | 2026-04-11T14:32:07Z |

## 5 Delivery Configuration

### 5.1 Retry Logic

| Attempt | Timing | Action |
|---|---|---|
| 1st | Immediate | Primary channel |
| 2nd | +2 minutes | Retry primary |
| 3rd | +10 minutes | Fallback channel |
| 4th | +30 minutes | Final retry — fallback |
| Failed | — | Log failure; alert SysAdmin for critical |

### 5.2 Rate Limiting

| Channel | Limit |
|---|---|
| Push | 20 / user / hour |
| Email | 50 / user / hour |
| SMS | 5 / user / hour |
| In-App | Unlimited |

### 5.3 Batch Consolidation

| Scenario | Consolidated Output |
|---|---|
| Foreman receives 15 individual timecard submissions | Single email: "15 timecards submitted for your review" |
| Employee receives multiple approvals | Single push: "3 timecards approved for this week" |
| Payroll Admin receives multiple late submissions | Daily digest email at 08:00 |

## 6 User Preferences

### 6.1 Configurable Options

| Option | Values | Default |
|---|---|---|
| Email notifications | On / Off per category | On (all) |
| Push notifications | On / Off per category | On (all) |
| SMS notifications | On / Off (critical only) | Off |
| Quiet hours | Start / End time | 22:00–06:00 |
| Digest mode | Real-time / Daily / Weekly | Real-time |
| Language | EN / ES | EN |

### 6.2 Non-Configurable (Mandatory) Notifications

**Mandatory — Cannot Be Disabled**

The following notifications are always delivered regardless of user preferences:

- **Pay stub delivery** (PR-N08)
- **Security alerts** (SY-N01 through SY-N04)
- **Hash chain integrity failures** (SY-N08)
- **Account lockout** (SY-N02)

## 7 Monitoring

### 7.1 Delivery Metrics

| Metric | Target | Alert Threshold |
|---|---:|---:|
| Email delivery rate | >99% | <95% |
| Push delivery rate | >98% | <90% |
| SMS delivery rate | >99% | <95% |
| Average email latency | <2 min | >5 min |
| Average push latency | <10 sec | >60 sec |
| Failed notification rate | <0.5% | >2% |

### 7.2 Health Dashboard

The notification health dashboard displays the following metrics in real time:

- Notifications sent — last 24h / 7d / 30d — by channel
- Delivery success/failure rates
- Average latency by channel
- Top 5 failed recipients
- Queue depth and processing rate

---

End of Appendix — Notifications

