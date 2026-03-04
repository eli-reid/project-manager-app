Settings Subsystem Architecture Specification

This document defines the authoritative architecture for the Settings subsystem. All generated code must follow this specification exactly. The subsystem replaces most .env usage with a database‑backed configuration engine.

1. Purpose

The Settings subsystem provides a centralized, database‑backed configuration engine that:

Initializes early in the application lifecycle.

Provides a three‑layer fallback: SQLite → .env → Laravel config defaults.

Exposes a clean API for reading and writing settings.

Supports namespaced settings per domain.

Caches values for performance.

Never breaks the application if the settings database is missing or corrupted.

This subsystem is a core module, not a domain module.

2. Folder Structure

All files must be placed under:

app/Core/Settings/
  Database/
    Migrations/
  Models/
  Providers/
    SettingsServiceProvider.php
  Resources/
    config/settings-db.php
  Services/
    SettingsSqliteService.php
    SettingsRepository.php
    SettingsCacheService.php

No settings code may be placed in:

AppServiceProvider

Domain providers

Random folders

resources/ (except the config file)

3. SettingsServiceProvider

Base Class

Illuminate\Support\ServiceProvider

register()

Merge the settings config file.

Bind the following as singletons:

SettingsRepository

SettingsCacheService

SettingsSqliteService

boot()

Initialize the settings database.

Apply dev‑mode .env fallback rules.

Never throw exceptions.

Never depend on domain providers.

Provider Order

This provider must be registered before all domain providers in config/app.php.

4. Settings Resolution Rules

Every settings read must follow this order:

SQLite value (authoritative)

.env fallback (if DB missing or key missing)

Laravel config default (final fallback)

This order must never be bypassed.

5. Database Schema

SQLite table:

settings
  id INTEGER PRIMARY KEY AUTOINCREMENT
  namespace TEXT NOT NULL
  key TEXT NOT NULL
  value TEXT
  type TEXT NOT NULL  -- string, int, bool, json, float
  updated_at TEXT
  UNIQUE(namespace, key)

6. SettingsSqliteService API

The service must implement:

class SettingsSqliteService
{
    public function isDatabaseAvailable(): bool;

    public function initializeDatabase(): void;

    public function get(string $key, $default = null);

    public function getInt(string $key, int $default = 0): int;

    public function getBool(string $key, bool $default = false): bool;

    public function getJson(string $key, array $default = []): array;

    public function set(string $key, $value, string $type = 'string'): void;

    public function all(string $namespace = null): array;
}

Required Behavior

get() must use the 3‑layer fallback.

set() must write to SQLite and invalidate cache.

getJson() must decode JSON safely.

all() must return namespaced settings.

7. SettingsRepository

The repository isolates database access and must not contain:

Business logic

Fallback logic

.env access

API:

class SettingsRepository
{
    public function find(string $namespace, string $key);
    public function save(string $namespace, string $key, $value, string $type);
    public function all(string $namespace = null);
}

8. SettingsCacheService

The cache layer must:

Cache settings reads.

Invalidate cache on writes.

Support namespace flushing.

API:

class SettingsCacheService
{
    public function remember(string $key, callable $callback);
    public function forget(string $key);
    public function flushNamespace(string $namespace);
}

9. Config File

Location:

app/Core/Settings/Resources/config/settings-db.php

Contents:

return [
    'dev_mode' => [
        'use_env_file' => true,
        'enabled_environments' => ['local', 'development', 'dev', 'testing'],
    ],
];

10. Initialization Rules

Inside SettingsServiceProvider::boot():

Resolve SettingsSqliteService.

Check dev‑mode rules.

If dev‑mode is active → skip DB initialization.

If DB is missing → call initializeDatabase().

Catch all exceptions.

Log warnings but never break the application.

The subsystem must not use $this->app->booted().

11. Domain Consumption Rules

Domains must never read .env directly.

Domains must use:

$settings = app(SettingsSqliteService::class);

$settings->get('scheduler.interval');
$settings->getBool('user.registration.enabled');
$settings->getJson('announcement.banner');

All settings must be namespaced.

12. Namespacing Rules

Settings keys must follow:

app.timezone
scheduler.interval
user.registration.enabled
announcement.banner.json

Flat keys are not allowed.

13. Admin UI (Optional)

If an admin UI is implemented:

Livewire components must live in the Settings module.

Views must live under Resources/Views/livewire/settings.

All writes must go through SettingsSqliteService.

Validation must be enforced per namespace.

14. Coding Standards

No logic in controllers.

No DB access in services except via repository.

No fallback logic in repository.

No settings logic in domain providers.

No .env reads outside the settings subsystem.

No global helpers for settings.

No static calls.

All services must be container‑resolved.

All settings must be namespaced.

All settings must be cached.

15. Copilot Behavior Rules

Copilot must:

Generate files only inside the Settings subsystem.

Follow the folder structure exactly.

Follow the API signatures exactly.

Follow the fallback rules exactly.

Follow the provider lifecycle exactly.

Never place settings logic in domain modules.

Never bypass the repository or cache layers.

Never generate .env reads outside the subsystem.