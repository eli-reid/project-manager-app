# cPanel Integration Rollout Plan (`project-manager-app`)

## Goal
Operate cPanel-backed mailbox management safely in production with flag-gated rollout, non-blocking behavior, and clear incident procedures.

## Implemented State (As-Built)

### Completed capabilities
- `services.cpanel` configuration exists and is settings-backed (`cpanel.*`).
- Admin endpoints exist for:
  - list/create/delete mailbox
  - reset password
  - suspend/unsuspend
  - list/create/delete forwarders
- Permission model is enforced by `manage-email-accounts` gate + middleware.
- User lifecycle integration exists (provision, deprovision, username-change sync).
- Password sync exists and is flag-gated by `cpanel.sync_user_passwords`.
- Async reliability controls exist for write-side operations:
  - queue toggle (`cpanel.queue_write_operations`)
  - idempotency TTL
  - retry/backoff settings
  - cooldown threshold/window
  - telemetry counters in cache
- Security hardening exists:
  - sensitive values masked in logs/context
  - local-part/domain sanitization on write operations

### Current operational gap
- Credential rotation is still a manual operator process and must be executed per environment.

## Runtime Flags and Controls

### Core controls
- `cpanel.auto_create_emails`
- `cpanel.auto_delete_emails`
- `cpanel.sync_user_passwords`
- `cpanel.queue_write_operations`

### Reliability controls
- `cpanel.idempotency_ttl_seconds`
- `cpanel.queue_tries`
- `cpanel.queue_backoff`
- `cpanel.failure_threshold`
- `cpanel.cooldown_seconds`
- `cpanel.telemetry_key_prefix`

## Staged Rollout Notes

### Stage 0: Safe baseline
- Keep `auto_create_emails=false`, `sync_user_passwords=false`.
- Set `queue_write_operations=true` and verify queue worker health.
- Confirm admin read/list endpoints and manual generation work.

### Stage 1: Controlled write rollout
- Enable `auto_create_emails=true` for mailbox provisioning on new users.
- Keep `sync_user_passwords=false` during initial stabilization.
- Monitor telemetry cache counters and warning logs for 24-48h.

### Stage 2: Password sync rollout
- Enable `sync_user_passwords=true`.
- Keep queueing enabled for write-side operations.
- Validate password update flow and forgot-password reset flow in production-like environment.

### Stage 3: Full operations
- Keep cooldown and retry/backoff tuned based on observed failure rates.
- Revisit `auto_delete_emails` default per business policy.

## Fallback and Rollback

### Immediate fallback (no deploy required)
- Set `cpanel.sync_user_passwords=false` to stop password sync traffic.
- Set `cpanel.auto_create_emails=false` to stop provisioning traffic.
- Keep `queue_write_operations=true` unless queue is degraded.

### Degraded mode fallback
- If cPanel outage persists, raise `cooldown_seconds` and/or lower `failure_threshold`.
- Pause queue workers for cPanel queue if necessary.

### Full rollback
- Disable all write-side flags (`auto_create_emails`, `auto_delete_emails`, `sync_user_passwords`).
- Keep read/list operations available for visibility.

## Operator Runbook (Incident + Recovery)

### Incident detection
- Signals:
  - rising `failure.*` telemetry counters
  - repeated `cPanel API request failed` warnings
  - cooldown key active for extended periods

### Triage steps
1. Validate cPanel connectivity and credentials outside app.
2. Check queue worker health and backlog depth.
3. Inspect app logs for masked cPanel errors and endpoint/function context.
4. Confirm whether failures are transient (network/timeout) or persistent auth/config.

### Containment steps
1. Disable `cpanel.sync_user_passwords` first.
2. Disable `cpanel.auto_create_emails` if provisioning failures continue.
3. Keep read-only operations enabled for support visibility.

### Recovery steps
1. Fix connectivity/credential/config issue.
2. Re-enable flags in order: provisioning, then password sync.
3. Watch telemetry counters and queue retries for at least one full business cycle.

## Credential Rotation Procedure

### When to rotate
- Any suspected leak of `CPANEL_API_TOKEN`.
- Scheduled secret hygiene window.
- Personnel/access changes.

### Rotation steps
1. Generate a new cPanel API token with least-privilege scope.
2. Update secret store/environment (`CPANEL_API_TOKEN`) per environment.
3. Sync settings value (`cpanel.api_token`) if using settings DB override.
4. Clear config/settings cache and restart queue workers.
5. Validate with a non-destructive operation (list email accounts).
6. Revoke old token after new token is verified.

### Verification checklist
- Admin cPanel list endpoint returns success.
- Write operation succeeds in staging/prod canary.
- No auth failures in logs for 15-30 minutes after cutover.

## Outstanding Items
- Execute credential rotation in all environments and record completion evidence.
- Decide policy default for `auto_delete_emails` and document rationale.

