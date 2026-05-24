# Production Promotion and Deploy Checklist

## Purpose
Use this checklist to safely promote code from development to production while preserving production data.

## Branch Strategy
- Development branch: daily feature integration.
- Production branch: deploy source of truth.
- Optional staging branch: pre-production verification.

## Branch Protection Rules (Git Host)
- [ ] Protect production branch from direct pushes.
- [ ] Require pull request approval before merge.
- [ ] Require CI checks to pass before merge.
- [ ] Restrict who can merge to production.

## Release Preparation (Before PR to Production)
- [ ] Confirm target commit is already merged and stable in development.
- [ ] Confirm no known blocker bugs remain.
- [ ] Run test suite required for release scope.
- [ ] Verify migration safety (no destructive commands, no data resets).
- [ ] Confirm root artifact cleanup is complete (no disposable test outputs in root).

## Data Safety Preconditions (Must Be True)
- [ ] Production `.env` is outside normal code updates or preserved between deployments.
- [ ] `storage/` is persistent across deployments.
- [ ] Main database is persistent and backed up.
- [ ] Settings SQLite path uses persistent location via `SETTINGS_DB_PATH`.
- [ ] `settings.data` is not managed as mutable production data in git pull flow.

## Production Promotion (PR Workflow)
- [ ] Open PR from development to production.
- [ ] Include release notes summary in PR description.
- [ ] Include migration notes in PR description.
- [ ] Include rollback notes in PR description.
- [ ] Merge after approvals and checks pass.
- [ ] Tag release on production branch (example: `vYYYY.MM.DD-N`).

## Production Deploy Steps
- [ ] Put application in maintenance mode if required by your process.
- [ ] Create backups:
  - [ ] Main database backup
  - [ ] `.env` backup
  - [ ] Settings SQLite backup (`SETTINGS_DB_PATH` target)
- [ ] Pull latest from production branch only.
- [ ] Install dependencies for production:
  - [ ] `composer install --no-dev --optimize-autoloader`
- [ ] Run migrations safely:
  - [ ] `php artisan migrate --force`
- [ ] Rebuild caches:
  - [ ] `php artisan optimize:clear`
  - [ ] `php artisan config:cache`
  - [ ] `php artisan optimize`
- [ ] Restart queue workers:
  - [ ] `php artisan queue:restart`
- [ ] Disable maintenance mode.

## Post-Deploy Verification
- [ ] App homepage loads.
- [ ] Authentication flow works.
- [ ] At least one write flow works (create/update path).
- [ ] Critical reports/pages load.
- [ ] Queue jobs process.
- [ ] Settings read and write work with production settings DB path.
- [ ] Logs show no new critical errors.

## Rollback Procedure
- [ ] Re-enable maintenance mode.
- [ ] Checkout previous production tag/commit.
- [ ] Restore `.env` backup if needed.
- [ ] Restore settings SQLite backup if needed.
- [ ] Restore main database backup if migration introduced data issues.
- [ ] Re-run cache rebuild and queue restart.
- [ ] Disable maintenance mode.
- [ ] Record incident notes and root cause.

## Commands To Never Run In Production
- `php artisan migrate:fresh`
- `php artisan migrate:reset` (unless under controlled rollback with full backups)
- Test/fuzz helper scripts from archive folders
- IDE helper generation commands

## Release Record Template
- Release tag:
- Production commit:
- PR link:
- Migration summary:
- Backups created at:
- Deployed by:
- Start time:
- End time:
- Verification result:
- Rollback required (yes/no):
- Notes:
