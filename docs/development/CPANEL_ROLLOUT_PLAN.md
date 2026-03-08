# cPanel Integration Rollout Plan (`project-manager-app`)

## Goal
Implement production-ready cPanel email management parity from `project-manager` into `project-manager-app`, while improving architecture, reliability, and security.

## Current Gap Summary
- UI references exist for company email actions, but backend routes/controllers are missing.
- No `services.cpanel` config block in `config/services.php`.
- No cPanel API client/service in `project-manager-app`.
- No user observer/job pipeline for email provisioning/sync.
- No feature test coverage for cPanel flows in `project-manager-app`.

## Rollout Phases

## Phase 1: Foundation + Parity (Required)
- [ ] Add cPanel config in `config/services.php`:
  - [ ] `url`, `username`, `api_token`, `domain`, `port`, `webmail_url`, `webmail_port`
  - [ ] `default_email_quota`, `auto_create_emails`, `sync_user_passwords`, `verify_ssl`
- [ ] Add `.env.example` keys for the above settings.
- [ ] Define/seed settings keys in settings system (`cpanel.*`) so values can be managed in admin settings UI.
- [ ] Create `CpanelApiClient` (single HTTP client wrapper):
  - [ ] URL building
  - [ ] auth header generation
  - [ ] timeout/retry policy
  - [ ] normalized response/error mapping
- [ ] Create `CpanelEmailService` (domain operations):
  - [ ] list accounts
  - [ ] create account
  - [ ] update password
  - [ ] delete account
  - [ ] create webmail session token/url
- [ ] Add missing user routes in `app/Core/User/Routes/users/admin.php`:
  - [ ] `admin.users.generate-company-email`
- [ ] Implement action/controller method for generate/regenerate company email.
- [ ] Ensure `User` model and DB schema support company email fields used by existing views.

### Phase 1 Acceptance Criteria
- [ ] Create/edit user screens no longer reference missing routes.
- [ ] Admin can generate/regenerate company email for a user.
- [ ] cPanel failures do not block user CRUD and return actionable errors.
- [ ] Feature tests pass for core generation flow.

## Phase 2: Async Lifecycle + Password Sync
- [ ] Add `UserObserver` integration for lifecycle hooks:
  - [ ] on created: dispatch provisioning job when enabled
  - [ ] on password changed: dispatch password sync job when enabled
  - [ ] on deleted: optionally dispatch mailbox deletion (flag-gated)
- [ ] Add queued jobs:
  - [ ] `ProvisionCompanyEmailJob`
  - [ ] `SyncCompanyEmailPasswordJob`
  - [ ] `DeleteCompanyEmailJob` (optional)
- [ ] Add idempotency checks in jobs to prevent duplicate mailbox creation.
- [ ] Add retry/backoff strategy for transient cPanel failures.
- [ ] Add status tracking fields/log context for provisioning outcomes.

### Phase 2 Acceptance Criteria
- [ ] User creation and password-change flows are non-blocking.
- [ ] Jobs retry safely and do not duplicate mailboxes.
- [ ] Operational logs include correlation IDs/user IDs and sanitized context.

## Phase 3: Admin Mailbox Management + Webmail UX
- [ ] Add admin email account management controller endpoints:
  - [ ] list/search
  - [ ] create standalone mailbox
  - [ ] reset mailbox password
  - [ ] suspend/unsuspend
  - [ ] delete
  - [ ] forwarders (optional)
- [ ] Add webmail launch route/controller (session-based redirect if supported).
- [ ] Add permissions and policy/gate integration:
  - [ ] `manage-email-accounts`
- [ ] Add UI pages/components for mailbox operations.

### Phase 3 Acceptance Criteria
- [ ] Admin can manage mailbox lifecycle from UI.
- [ ] Authorization enforced for all mailbox actions.
- [ ] Webmail launch works or degrades gracefully to login URL.

## Security and Reliability Requirements (All Phases)
- [ ] Never log API token or plaintext generated passwords.
- [ ] Mask sensitive fields in logs and exceptions.
- [ ] Validate/sanitize email local-part and domain usage.
- [ ] Add circuit breaker behavior for repeated cPanel outages (cooldown window).
- [ ] Add telemetry counters for success/failure rates.

## Testing Plan
- [ ] Unit tests for `CpanelApiClient` URL building/auth/error mapping.
- [ ] Unit tests for `CpanelEmailService` logic and edge cases.
- [ ] Feature tests for generate/regenerate company email route and permissions.
- [ ] Observer/job tests for dispatch conditions and idempotency.
- [ ] HTTP fake tests for cPanel API status codes (200, 403, 429, 5xx, timeout).

## Data and Migration Checklist
- [ ] Confirm `users` table has `company_email` and any status fields required.
- [ ] Add migrations for missing fields/indexes if needed.
- [ ] Backfill `company_email` for existing users where possible.

## Deployment Checklist
- [ ] Add env values in each environment.
- [ ] Validate queue worker is running before enabling auto-create/sync.
- [ ] Enable feature flags in stages:
  - [ ] read-only/list first
  - [ ] manual generation
  - [ ] auto-create on user creation
  - [ ] password sync
- [ ] Monitor logs/metrics for first rollout window.

## Open Decisions
- [ ] Should mailbox deletion be enabled by default on user delete?
- [ ] Should generated mailbox passwords be surfaced to admins once, or never?
- [ ] Should webmail SSO be mandatory, optional, or postponed?
- [ ] Which operations require queue-only execution (recommended: all write ops)?

## Suggested Execution Order
1. Phase 1 config + backend route fix + generate/regenerate action.
2. Phase 1 tests and production hardening pass.
3. Phase 2 observer + jobs + retries + idempotency.
4. Phase 3 admin management and webmail UX.
