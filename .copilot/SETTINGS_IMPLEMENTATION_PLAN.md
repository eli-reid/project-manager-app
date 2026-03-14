# Settings Subsystem - Implementation Plan

## Current State Analysis

The `project-manager-app` has a **partially implemented** Settings subsystem:

| Component | Status | Details |
|-----------|--------|---------|
| **SettingsSqlite Model** | ✅ 90% Done | Full encryption, caching, metadata support |
| **SettingsSqliteService** | ✅ 85% Done | Most methods implemented but missing Repository layer |
| **SettingServiceProvider** | ⚠️ 60% Done | Uses `$this->app->booted()` (violates spec) |
| **Traits** | ✅ Complete | EncryptableSettings trait fully functional |
| **Repository Pattern** | ❌ Missing | Critical gap - direct DB access in Service |
| **SettingsCacheService** | ❌ Missing | Caching scattered in Service & Model |
| **Config File** | ❌ Missing | No `config/settings-db.php` in project-manager-app |
| **Routes/Admin UI** | ⚠️ Stubbed | Routes defined but no controllers/views |
| **Helper Functions** | ❌ Missing | No global helpers - relies on container |
| **Migrations** | ❌ Missing | Table created dynamically, not via migration |

---

## Critical Issues to Fix 🚨

| Issue | Severity | Impact | Fix |
|-------|----------|--------|-----|
| **SettingsServiceProvider uses `$this->app->booted()`** | HIGH | Violates Laravel 12 best practices | Use early provider registration |
| **Repository pattern missing** | HIGH | Violates SoC - Service has DB logic | Create `SettingsRepository` class |
| **Cache logic spread across 3 files** | HIGH | Code duplication & hard to maintain | Consolidate into `SettingsCacheService` |
| **Config file missing from project-manager-app** | HIGH | Development mode can't work | Create config in Resources/config |
| **No helper functions** | MEDIUM | Poor DX compared to spec | Create global helpers file |
| **Table created dynamically** | MEDIUM | No version control/auditability | Create proper migration file |
| **Namespace mismatch on Services** | MEDIUM | Inconsistent import paths | Align all Services namespace |

---

## Architecture Alignment ✅

Your project follows **modular Core architecture** perfectly:

```
app/Core/Settings/
  ├── Actions/          ← Custom actions (optional)
  ├── Database/
  │   └── Migrations/   ← Proper migration files (TO CREATE)
  ├── Models/
  │   └── SettingsSqlite.php  ✅ Exists
  ├── Observers/        ← Mutations/side effects (optional)
  ├── Permissions/      ← RBAC controls (optional)
  ├── Providers/
  │   └── SettingServiceProvider.php  ⚠️ Needs refactoring
  ├── Resources/
  │   ├── config/
  │   │   └── settings-db.php  ❌ TO CREATE
  │   ├── Views/        ← Admin UI views (optional)
  │   └── Livewire/     ← Admin UI components (optional)
  ├── Routes/           ✅ Exists but empty
  ├── Services/
  │   ├── SettingsSqliteService.php  ✅ ~85% done
  │   ├── SettingsRepository.php     ❌ TO CREATE
  │   └── SettingsCacheService.php   ❌ TO CREATE
  ├── Traits/
  │   └── EncryptableSettings.php    ✅ Complete
  └── View/             ← View components (optional)
```

---

## Step-by-Step Implementation Plan 📋

### Phase 1: Foundation (Critical Path)
Must complete before any feature work:

#### 1. **Copy config file** (5 min)
   - Copy from `project-manager/config/settings-db.php` → `project-manager-app/app/Core/Settings/Resources/config/settings-db.php`
   - Verify cache, encryption, and dev-mode settings
   - Ensure `settings_sqlite` connection exists in `config/database.php`

#### 2. **Create SettingsRepository** (20 min)
   - Pure data access layer - NO business logic
   - Methods: `find()`, `save()`, `all()`, `delete()`
   - Handle only queries and raw saves
   - NO encryption, NO fallbacks, NO caching

#### 3. **Create SettingsCacheService** (15 min)
   - Centralize all caching logic
   - Methods: `remember()`, `forget()`, `flushNamespace()`
   - Support namespace-based cache invalidation
   - Use consistent cache key format

#### 4. **Refactor SettingsSqliteService** (30 min)
   - Remove repository calls (use SettingsRepository instead)
   - Remove cache calls (use SettingsCacheService instead)
   - Keep only business logic: fallbacks, type validation, convenience methods
   - Methods remain: `get()`, `set()`, `getInt()`, `getBool()`, `getJson()`, etc.

#### 5. **Create SettingsServiceProvider (Lambda-free)** (15 min)
   - Register singletons in `register()` - NO lambdas
   - Initialize DB in `boot()` - NO `$this->app->booted()`
   - Use early initialization pattern
   - Catch all exceptions, log warnings

#### 6. **Create database migration** (10 min)
   - Move schema from Model → proper migration file
   - Follow Laravel 12 conventions
   - Ensure idempotency (skip if table exists)
   - Add all indexes for performance

---

### Phase 2: Developer Experience
Makes the system user-friendly:

#### 7. **Create global helpers** (10 min)
   - `setting($key, $default)` - Read/access service
   - `settings()` - Get service instance
   - Auto-autoload in `composer.json` PSR-4

#### 8. **Create SettingsObserver** (optional, 15 min)
   - Track changes for audit logs
   - Dispatch events on mutations
   - Clear related caches

#### 9. **Create seeders** (optional, 15 min)
   - Default settings for each domain
   - Test data fixtures
   - Environment-aware defaults

---

### Phase 3: Admin UI (Optional)
Management interface for non-developers:

#### 10. **Create Livewire components** (1-2 hours)
    - SettingsGroup component (list settings by group)
    - SettingForm component (edit individual settings)
    - SettingsList component (paginated table)

#### 11. **Create routes & controllers** (30 min)
    - `GET /admin/settings` - List all groups
    - `GET /admin/settings/{group}` - Edit group
    - `POST /admin/settings/{group}` - Save group
    - `PUT /admin/settings/{key}` - Quick edit
    - Policy: `can:access-admin`

#### 12. **Create views** (30 min)
    - Bootstrap grid layout for settings groups
    - Form components (text, select, textarea, password, etc.)
    - Validation error display
    - Success toast notifications

---

## File Dependencies 🔗

**Import Order (must follow to avoid circular dependencies):**

```
1. config/settings-db.php           ← Config only
2. SettingsRepository               ← No deps except config
3. SettingsCacheService             ← No deps except config
4. EncryptableSettings trait        ← No deps except config
5. SettingsSqlite Model             ← Deps: trait, cache service
6. SettingsSqliteService            ← Deps: repository, cache, model
7. SettingsServiceProvider          ← Deps: service, config
8. Global helpers                   ← Deps: service
```

---

## Critical Code Patterns 🎯

**❌ DON'T DO (Violates Spec):**
```php
// In SettingsServiceProvider
$this->app->booted(fn () => {...});  // ❌ Wrong lifecycle

// In Service
SettingsSqlite::where(...)->get();   // ❌ Repository bypassed

// In Repository  
$this->cache->remember(...);         // ❌ Cache logic leaks

// In helpers
function setting() { ... }           // ❌ Global helper without namespace

// In Model
public function setValue() { ... }   // ❌ Don't mix mutation logic here
```

**✅ DO THIS (Spec-Compliant):**
```php
// In SettingsServiceProvider->register()
$this->app->singleton(SettingsRepository::class);
$this->app->singleton(SettingsCacheService::class);
$this->app->singleton(SettingsSqliteService::class, function ($app) {
    return new SettingsSqliteService(
        $app->make(SettingsRepository::class),
        $app->make(SettingsCacheService::class)
    );
});

// In Service (business logic only)
public function get(string $key, $default = null) {
    if ($this->shouldUseEnvInDev()) {
        return $this->getFromEnv($key, $default);
    }
    
    return $this->cache->remember(
        "settings.{$key}",
        fn () => $this->repository->find('app', $key) ?? $default
    );
}

// In Repository (data access only)
public function find(string $namespace, string $key) {
    return SettingsSqlite::where('key', $key)->first();
}

// In Cache Service (caching only)
public function remember(string $key, callable $callback) {
    return Cache::remember($key, 3600, $callback);
}
```

---

## Testing Strategy 🧪

After each phase:

```bash
# Phase 1: Foundation
php artisan migrate:fresh
php artisan test Feature/SettingsRepositoryTest
php artisan test Feature/SettingsCacheServiceTest
php artisan test Feature/SettingsSqliteServiceTest

# Phase 2: DX
php artisan test Feature/SettingsHelperTest

# Phase 3: Admin UI
php artisan test Feature/SettingsAdminTest
```

---

## Configuration Validation ✓

After setup, verify:

```php
// .env required
SETTINGS_USE_ENV_IN_DEV=false  # Or true for dev mode
SETTINGS_DB_PATH=database/settings.sqlite
SETTINGS_CACHE_ENABLED=true
SETTINGS_CACHE_TTL=3600

// config/settings-db.php checks
dev_mode.enabled_environments = ['local', 'development', 'testing']
encryption.sensitive_keys = ['app_key', 'db_password', ...]
env_mappings = [...]  # Correct .env fallback keys

// config/database.php checks
connections.settings_sqlite = [
    'driver' => 'sqlite',
    'database' => database_path('settings.sqlite'),
]

// Provider registration (config/app.php)
'providers' => [
    App\Core\Settings\Providers\SettingServiceProvider::class,  // BEFORE domain providers
    App\Core\User\Providers\FortifyServiceProvider::class,
    // ...
]
```

---

## Risk Mitigation 🛡️

| Risk | Mitigation |
|------|-----------|
| Database locked during migrations | Use `-F` flag: `php artisan migrate --force` |
| Cache inconsistency | Call `php artisan optimize:clear` after any change |
| Encryption key mismatch | Backup `APP_KEY` before changes |
| Old code still calling Model directly | Add deprecation warnings in SettingsSqlite methods |
| Circular dependencies | Import order strictly enforced (see File Dependencies section) |

---

## Success Criteria ✅

After complete implementation:

- ✅ No direct DB access outside Repository
- ✅ No cache logic outside SettingsCacheService
- ✅ No .env reads outside SettingsSqliteService
- ✅ All settings namespaced (e.g., `app.timezone`, not `app_timezone`)
- ✅ Provider uses `register()/boot()` not `$this->app->booted()`
- ✅ Table created via migration, not dynamically
- ✅ 100% test coverage for Settings classes
- ✅ Global helpers available throughout app
- ✅ Admin UI fully functional (optional)

---

## Recommended Execution Order

**Day 1 (Morning):** Phase 1 (60 min)
- Copy config, create Repository, Cache Service, refactor Service, create Provider

**Day 1 (Afternoon):** Phase 2 (30 min)
- Global helpers, basic tests

**Day 2:** Phase 3 (2-3 hours, optional)
- Admin UI components & routes

---

## Status Tracker

Use this section to track which files have been completed:

- [x] 1. Config file copied
- [x] 2. SettingsRepository created
- [x] 3. SettingsCacheService created
- [x] 4. SettingsSqliteService refactored
- [x] 5. SettingsServiceProvider created
- [x] 6. Database migration created
- [x] 7. Global helpers created
- [x] 8. SettingsObserver created (optional)
- [x] 9. Seeders created (optional)
- [ ] 10. Livewire components created (optional)
- [ ] 11. Routes & controllers created (optional)
- [ ] 12. Views created (optional)

## Quick Start Guide

### 1. Rebuild Composer Autoloader
```bash
composer dump-autoload
```

### 2. Run Migrations
```bash
php artisan migrate --force
```

### 3. Seed Default Settings (optional)
```bash
php artisan db:seed --class="Database\Seeders\DatabaseSeeder"
```

### 4. Clear Caches
```bash
php artisan optimize:clear
```

### 5. Test Helper Functions
```bash
php artisan tinker

# Test helpers
>>> setting('app.name')
>>> settings()->all()
>>> setting_set('test.key', 'test value')
>>> setting_bool('app.debug')
```

## Next Steps: Phase 3 (Optional Admin UI)

Phase 3 includes:
- Livewire components for settings management UI
- Admin routes and controllers
- Bootstrap-based views for settings editing
- Real-time validation and feedback

Proceed with Phase 3? (Optional)

## Phase 1 Completion Summary ✅

**All Phase 1 Foundation tasks are complete!**

### Files Created/Modified:
1. ✅ `app/Core/Settings/Resources/config/settings-db.php` - Config file
2. ✅ `app/Core/Settings/Services/SettingsRepository.php` - Data access layer (NEW)
3. ✅ `app/Core/Settings/Services/SettingsCacheService.php` - Caching layer (NEW)
4. ✅ `app/Core/Settings/Services/SettingsSqliteService.php` - REFACTORED (removed DB/cache logic)
5. ✅ `app/Core/Settings/Providers/SettingServiceProvider.php` - REFACTORED (removed `$this->app->booted()`)
6. ✅ `database/migrations/0001_01_01_000003_create_settings_table.php` - Migration (NEW)
7. ✅ `config/database.php` - Added `settings_sqlite` connection
8. ✅ `config/app.php` - Registered SettingServiceProvider FIRST

### Architecture Achievements:
- ✅ **Separation of Concerns**: Repository (data) | Cache (caching) | Service (business logic)
- ✅ **Dependency Injection**: All services injected via constructor
- ✅ **No Lambda Functions**: Provider uses clean `register()/boot()` pattern
- ✅ **Early Initialization**: Settings boot in `boot()` method, not `$this->app->booted()`
- ✅ **Config Merging**: Settings config auto-merged in register()
- ✅ **Error Resilience**: All exceptions caught, never breaks app
- ✅ **Code DRY**: Cache logic centralized, DB logic in repo

---

## Phase 2 Completion Summary ✅

**All Phase 2 Developer Experience tasks are complete!**

### Files Created/Modified:
1. ✅ `app/Core/Settings/Helpers/SettingsHelpers.php` - Global helper functions (NEW)
2. ✅ `composer.json` - Auto-load helpers file
3. ✅ `app/Core/Settings/Observers/SettingsObserver.php` - Auto cache clearing (NEW)
4. ✅ `app/Core/Settings/Providers/SettingServiceProvider.php` - UPDATED (registered observer)
5. ✅ `app/Core/Settings/Database/Seeders/SettingsSeeder.php` - Default settings (NEW)
6. ✅ `app/Core/Database/Seeders/CoreSeeder.php` - Core initialization (NEW)

### Global Helper Functions Available:

| Function | Usage | Returns |
|----------|-------|---------|
| `setting($key, $default)` | Get setting by key | mixed |
| `setting()` | Get service instance | SettingsSqliteService |
| `settings()` | Get service instance | SettingsSqliteService |
| `setting_int($key, $default)` | Get as integer | int |
| `setting_bool($key, $default)` | Get as boolean | bool |
| `setting_json($key, $default)` | Get as JSON array | array |
| `setting_has($key)` | Check if exists | bool |
| `setting_set($key, $value, $desc)` | Set a value | bool |

### Helper Usage Examples:

```php
// Get settings
$appName = setting('app.name');
$debug = setting_bool('app.debug', false);
$timezones = setting_json('app.timezones', []);

// Set settings
setting_set('feature.new_setting', 'value', 'Feature description');

// Direct service access
settings()->all();
settings()->getGroup('app');
settings()->getAllGrouped();

// With defaults
$timezone = setting('app.timezone', 'UTC');
```

### Observer Features:

- ✅ **Auto Cache Clearing**: Clears all relevant caches when settings change
- ✅ **Audit Logging**: Optional logging of all settings changes
- ✅ **Event Hooks**: `created`, `updated`, `deleted`, `restored` event handlers
- ✅ **Error Resilience**: Failures in caching don't break app

### Seeder Features:

- ✅ **SettingsSeeder**: Seeds default app, system, and feature settings
- ✅ **Timezone Options**: 18 timezone options pre-configured
- ✅ **updateOrCreate Logic**: Won't duplicate if already seeded
- ✅ **CoreSeeder**: Master seeder for all core initialization
- ✅ **Run Command**: `php artisan db:seed --class="Database\Seeders\DatabaseSeeder"`
