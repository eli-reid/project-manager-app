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
- [x] Add mailbox password reset endpoint and service method.
- [x] Add suspend mailbox endpoint and service method.
- [x] Add unsuspend mailbox endpoint and service method.
- [x] Add forwarder create endpoint and service method.
- [x] Add forwarder list endpoint and service method.
- [x] Add forwarder delete endpoint and service method.
- [x] Add request validation for each new endpoint.
- [x] Add HTTP fake feature tests for all new operations.

## 4. Password Sync Implementation
- [x] Implement `sync_user_passwords` behavior in user password change flow.
- [x] Ensure behavior is flag-gated and non-blocking.
- [x] Add tests for enabled/disabled paths.

## 5. Async Reliability Hardening
- [x] Add queued jobs for write-side cPanel operations where needed.
- [x] Add idempotency checks for create/delete flows.
- [x] Add retry/backoff handling for transient failures.
- [x] Add circuit breaker/cooldown behavior for repeated outages.
- [x] Add telemetry counters for success/failure rates.

## 6. Security Hardening
- [x] Ensure no API token/plaintext password is logged.
- [x] Mask sensitive fields in logs and exception contexts.
- [x] Validate/sanitize email local-part/domain input on write operations.
- [ ] Rotate any exposed cPanel credentials and verify secret handling process.
	Note: This is an operator-run environment task and is documented in `docs/development/CPANEL_ROLLOUT_PLAN.md` under `Credential Rotation Procedure`.

## 7. Docs and Rollout Alignment
- [x] Update rollout doc to reflect actual implemented state.
- [x] Replace stale checklist items with current gaps.
- [x] Add staged rollout notes (flags, sequencing, fallback).
- [x] Add operator runbook for incident handling and rollback.

## Acceptance Criteria
- [x] Required cPanel endpoints are implemented and permissioned.
- [x] Generate/regenerate user company-email workflow is fully validated.
- [x] Advanced mailbox operations are covered by tests.
- [x] Password sync and reliability controls are verified.
- [ ] Security checks pass with no sensitive leakage.
