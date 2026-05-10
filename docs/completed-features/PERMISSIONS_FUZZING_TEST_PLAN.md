# Permissions & Security Fuzzing Test Plan

**Objective:** In-depth testing of authorization boundaries, access controls, and input validation

## Test Users Created

| Name | Email | Role | Status | ID |
|------|-------|------|--------|-----|
| Admin Test | admin.test@example.com | Admin | Active | `01kr9t5gwph9cym8njm7kdwfsg` |
| Regular User | regular.user@example.com | User | Active | `01kr9t5gyrajmpvsm354hmkf0z` |
| Inactive User | inactive.user@example.com | User | Inactive | `01kr9t5gyv4x27m7rk8bp37v53` |
| No Admin | no.admin@example.com | User | Active | `01kr9t5gyzky9tjja4pmh31zwj` |

**Test Credentials:** `FuzzTestPass123!`

## Test Categories

### 1. Admin-Only Endpoints (401/403 Expectations)
- [ ] GET `/admin/users` — non-admin should get 403
- [ ] GET `/admin/settings` — non-admin should get 403
- [ ] GET `/admin/cpanel/manage/email-accounts` — non-admin should get 403
- [ ] GET `/admin/projects` — non-admin should get 403
- [ ] POST `/admin/users` — non-admin should get 403
- [ ] PUT `/admin/users/{id}` — non-admin should get 403
- [ ] DELETE `/admin/users/{id}` — non-admin should get 403

### 2. Cross-User Resource Access
- [ ] Can user A view user B's timecards? (Should be 403)
- [ ] Can user A edit user B's timecards? (Should be 403)
- [ ] Can user A delete user B's daily reports? (Should be 403)
- [ ] Can user A view/edit shared documents without password? (Should be 403 or require password)

### 3. Inactive User Restrictions
- [ ] Can inactive user log in? (Should fail)
- [ ] Can inactive user access `/dashboard`? (Should redirect/403)
- [ ] Does inactive user show in lists? (Should not, or marked disabled)

### 4. Input Fuzzing (Malicious Data)
- [ ] SQL injection in search fields
- [ ] XSS payloads in form inputs
- [ ] Oversized inputs
- [ ] Invalid ULIDs in URL parameters
- [ ] Negative numbers where positive expected
- [ ] Unicode/special characters in text fields

### 5. Privilege Escalation Attempts
- [ ] Can regular user set `is_admin=true` on themselves?
- [ ] Can regular user access password reset for admin account?
- [ ] Can regular user modify database via hidden form fields?
- [ ] Can regular user view audit logs?

### 6. State Transition Abuse
- [ ] Can user approve their own submittal?
- [ ] Can user approve timecard twice?
- [ ] Can user revert approved state?
- [ ] Can user cancel others' orders?

### 7. Rate Limiting & Brute Force
- [ ] Multiple failed login attempts (should throttle)
- [ ] Rapid API requests (should rate limit)
- [ ] Bulk operations (should limit)

## Test Results Summary

*To be populated during execution*

---

## Detailed Test Cases

### Category 1: Admin-Only Endpoints

**Test 1.1: Regular user attempts GET /admin/users**
- Expected: 403 Forbidden or redirect to login
- Actual: *(to be tested)*
- Status: [ ]

**Test 1.2: Regular user attempts GET /admin/settings**
- Expected: 403 Forbidden
- Actual: *(to be tested)*
- Status: [ ]

**Test 1.3: Inactive user attempts GET /admin/projects**
- Expected: Redirect to login (inactive cannot access)
- Actual: *(to be tested)*
- Status: [ ]

### Category 2: Cross-User Access

**Test 2.1: User A views User B's timecard (direct URL)**
- User A: `regular.user@example.com`
- User B: `no.admin@example.com`
- URL: `/timecards/{userB_timecard_id}`
- Expected: 403 Forbidden or redirect
- Actual: *(to be tested)*
- Status: [ ]

**Test 2.2: User A attempts to edit User B's daily report**
- Method: PUT to `/dailies/{userB_daily_id}`
- Expected: 403 Forbidden
- Actual: *(to be tested)*
- Status: [ ]

### Category 3: Input Fuzzing

**Test 3.1: SQL Injection in timecard notes**
- Payload: `notes = "'; DROP TABLE timecards; --"`
- Expected: Escaped/rejected
- Actual: *(to be tested)*
- Status: [ ]

**Test 3.2: XSS in announcement title**
- Payload: `title = "<script>alert('XSS')</script>"`
- Expected: Escaped on display
- Actual: *(to be tested)*
- Status: [ ]

**Test 3.3: Invalid ULID in URL**
- URL: `/admin/users/invalid-ulid-12345`
- Expected: 404 Not Found
- Actual: *(to be tested)*
- Status: [ ]

**Test 3.4: Negative quantity in stock order**
- Payload: `quantity = -100`
- Expected: Validation error
- Actual: *(to be tested)*
- Status: [ ]

### Category 4: Privilege Escalation

**Test 4.1: Regular user attempts to set is_admin=true**
- Method: PUT to `/admin/users/{own_id}`
- Payload: `is_admin = true`
- Expected: Ignored or 403
- Actual: *(to be tested)*
- Status: [ ]

**Test 4.2: Regular user accesses password reset link for admin**
- Method: GET `/password/reset/{admin_reset_token}`
- Expected: 403 or invalid token
- Actual: *(to be tested)*
- Status: [ ]

### Category 5: State Transition Abuse

**Test 5.1: User approves own submittal**
- Scenario: User creates submittal, becomes reviewer, approves self
- Expected: Prevented or restricted
- Actual: *(to be tested)*
- Status: [ ]

**Test 5.2: User approves timecard twice**
- Scenario: User approves timecard, then submits another approval
- Expected: Rejected (already approved)
- Actual: *(to be tested)*
- Status: [ ]

---

## Vulnerability Classes to Check

- [ ] Insecure Direct Object References (IDOR)
- [ ] Broken Access Control
- [ ] Cross-Site Scripting (XSS)
- [ ] SQL Injection
- [ ] Cross-Site Request Forgery (CSRF)
- [ ] Privilege Escalation
- [ ] Information Disclosure
- [ ] Race Conditions in state transitions

