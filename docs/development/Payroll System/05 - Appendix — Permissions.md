
# Appendix — Permissions

---

## 1 Overview

The payroll system enforces Role-Based Access Control (RBAC) to protect sensitive employee and financial data. Every action — read, write, approve, or configure — is governed by the user's assigned role. This appendix defines all roles, their permissions, and the policies that govern access.

## 2 Role Definitions

### 2.1 Role Hierarchy

```text
System Admin
├── Controller
│   └── Payroll Admin
│       └── Project Manager
│           └── Foreman
│               └── Field Worker
└── Auditor (read-only, cross-cutting)
````

### 2.2 Role Descriptions

## 3 Permission Matrix

### 3.1 Employee Master

### 3.2 Timecards

### 3.3 Payroll Processing

### 3.4 Reporting

### 3.5 System Administration

## 4 Access Control Policies

### 4.1 Authentication

### 4.2 Authorization Rules

### 4.3 Data Classification

## 5 Crew and Project Assignments

### 5.1 Crew Assignment

*   Foreman is assigned to one or more crews.
*   Crew membership is managed by Payroll Admin or System Admin.
*   Foreman permissions apply only to members of assigned crews.
*   An employee can belong to multiple crews but has one primary Foreman.

### 5.2 Project Assignment

*   Project Manager is assigned to one or more projects.
*   PM permissions apply only to assigned projects.
*   Project assignments are managed by Payroll Admin or System Admin.

### 5.3 Cross-Project Work

*   Employee submits timecard normally for any project they work on.
*   The assigned Foreman for that project approves the timecard.
*   If no Foreman is assigned to the project, PayAdmin receives the escalation for approval.

## 6 Special Permissions

### 6.1 Emergency Access

### 6.2 API Access

## 7 Role Lifecycle

### 7.1 Onboarding

1.  System Admin creates user account.
2.  System Admin assigns role(s).
3.  System Admin assigns crew and/or project.
4.  User receives activation email.
5.  User completes MFA enrollment.
6.  Access is active.

### 7.2 Role Change

7.  System Admin modifies the user's role.
8.  Previous permissions are revoked immediately.
9.  New permissions become effective immediately.
10. Audit log records the change with timestamp and acting administrator.

### 7.3 Offboarding

11. System Admin sets user status to **disabled**.
12. All active sessions are terminated.
13. API keys are revoked.
14. Account is retained for audit purposes.
15. Data is retained per organizational retention policies.

## 8 Compliance Requirements

*End of Appendix — Permissions*

```
```
