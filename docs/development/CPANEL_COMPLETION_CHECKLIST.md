# cPanel Completion Checklist

## Goal
Complete cPanel parity and hardening work in `project-manager-app`.

## Status Legend
- [x] Completed
- [ ] Remaining

## 1. Route and Action Parity
- [x] Add generate/regenerate company email admin action route.
- [x] Add UI entry point for generate/regenerate action on admin users page.
- [x] Add feature test coverage for generate/regenerate success and forbidden paths.

## 2. Permission Model Parity
- [x] Introduce dedicated `cpanel.manage-email-accounts` permission definition.
- [x] Register and synchronize the permission via permission registry.
- [x] Add `manage-email-accounts` gate support.
- [x] Replace admin-only cPanel endpoint gating with permission-based middleware.
- [x] Add tests for authorized/unauthorized cPanel endpoint access.

## 3. Advanced Mailbox Operations
- [ ] Add mailbox password reset endpoint and service method.
- [ ] Add suspend mailbox endpoint and service method.
- [ ] Add unsuspend mailbox endpoint and service method.
- [ ] Add forwarder create endpoint and service method.
- [ ] Add forwarder list endpoint and service method.
- [ ] Add forwarder delete endpoint and service method.
- [ ] Add request validation for each new endpoint.
- [ ] Add HTTP fake feature tests for all new operations.

## 4. Password Sync Implementation
- [ ] Implement `sync_user_passwords` behavior in user password change flow.
- [ ] Ensure behavior is flag-gated and non-blocking.
- [ ] Add tests for enabled/disabled paths.

## 5. Async Reliability Hardening
- [ ] Add queued jobs for write-side cPanel operations where needed.
- [ ] Add idempotency checks for create/delete flows.
- [ ] Add retry/backoff handling for transient failures.
- [ ] Add circuit breaker/cooldown behavior for repeated outages.
- [ ] Add telemetry counters for success/failure rates.

## 6. Security Hardening
- [ ] Ensure no API token/plaintext password is logged.
- [ ] Mask sensitive fields in logs and exception contexts.
- [ ] Validate/sanitize email local-part/domain input on write operations.
- [ ] Rotate any exposed cPanel credentials and verify secret handling process.

## 7. Docs and Rollout Alignment
- [ ] Update rollout doc to reflect actual implemented state.
- [ ] Replace stale checklist items with current gaps.
- [ ] Add staged rollout notes (flags, sequencing, fallback).
- [ ] Add operator runbook for incident handling and rollback.

## Acceptance Criteria
- [ ] Required cPanel endpoints are implemented and permissioned.
- [ ] Generate/regenerate user company-email workflow is fully validated.
- [ ] Advanced mailbox operations are covered by tests.
- [ ] Password sync and reliability controls are verified.
- [ ] Security checks pass with no sensitive leakage.
