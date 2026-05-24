# Manual SSH Deploy Checklist

## Purpose
Use this runbook for manual production deployments over SSH with data safety controls.

## Preconditions
- [ ] Deployment source branch is `production`.
- [ ] Production server has PHP, Composer, and required extensions installed.
- [ ] `APP_ENV=production` and `APP_DEBUG=false` are set in production `.env`.
- [ ] Persistent data paths are in place:
  - [ ] Main database is persistent and backed up.
  - [ ] `storage/` is persistent.
  - [ ] `SETTINGS_DB_PATH` points to a persistent location.

## Variables to set in your shell
```bash
APP_DIR="/var/www/project-manager-app"
BRANCH="production"
```

## 1. Connect and start a deploy session
```bash
ssh your-user@your-server
cd "$APP_DIR"
```

## 2. Enable maintenance mode (optional but recommended)
```bash
php artisan down --render="errors::503"
```

## 3. Create backups before code update
```bash
# Backup env
cp .env ".env.backup.$(date +%F-%H%M%S)"

# Backup settings SQLite if used
if [ -n "$SETTINGS_DB_PATH" ] && [ -f "$SETTINGS_DB_PATH" ]; then
  cp "$SETTINGS_DB_PATH" "${SETTINGS_DB_PATH}.backup.$(date +%F-%H%M%S)"
fi

# Backup main DB (example placeholder)
# mysqldump -u USER -p DATABASE > "/var/backups/pm-app-$(date +%F-%H%M%S).sql"
```

## 4. Pull production code only
```bash
git fetch origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"
```

## 5. Install production dependencies
```bash
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
```

## 6. Run database migrations safely
```bash
php artisan migrate --force
```

## 7. Rebuild caches and restart workers
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan queue:restart
```

## 8. Health checks
```bash
php artisan about
php artisan migrate:status
```

Validation checklist:
- [ ] Home/dashboard loads.
- [ ] Login works.
- [ ] One write flow works (create/update).
- [ ] Queue jobs process.
- [ ] Settings load successfully from `SETTINGS_DB_PATH`.

## 9. Disable maintenance mode
```bash
php artisan up
```

## 10. Rollback (if needed)
```bash
php artisan down --render="errors::503"

# Checkout previous known-good tag or commit
# git checkout vYYYY.MM.DD-N

composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

If database schema changes caused issues, restore database backups and settings backup before bringing app back up.

## Never run in production
- `php artisan migrate:fresh`
- `php artisan migrate:reset` (unless performing a controlled full restore)
- test/fuzz helper scripts from archive folders
- IDE helper generation commands
