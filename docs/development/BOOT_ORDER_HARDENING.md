# Boot Order Hardening — Provider Registration Safety

## Why This Exists

Domain service providers are auto-discovered and registered dynamically by `DomainServiceProvider`.
This is flexible but introduces three hidden risks:

1. **Non-deterministic load order** — `File::glob()` returns paths in filesystem order, which differs
   between Windows NTFS, Linux ext4, and macOS HFS+. Two developers or two CI environments
   can get a different provider boot sequence.
2. **Silent key overwrite** — Both `NotificationRegistry` and `PermissionRegistry` stored
   definitions in an associative array keyed by the definition key, so a second `registerDefinitions`
   or `registerPermissions` call with the same key silently replaced the first, with no trace in
   logs.
3. **Hidden cross-domain coupling** — Nothing prevented domain `A`'s boot logic from depending on
   domain `B` already being booted, which could work locally but break on a different machine.

---

## Changes Implemented

### 1. Deterministic Domain Provider Load Order

**File:** `app/Domains/Providers/DomainServiceProvider.php`
**Method:** `register()`

Added `sort($providerFiles)` after the glob call.

```php
$providerFiles = File::glob($domainProvidersPath.'/*/Providers/*ServiceProvider.php') ?: [];
sort($providerFiles);   // ← guarantees alphabetical, cross-platform order
```

**Effect:** Domains boot in the same ASCII-sorted order on every machine and in CI:
Addresses → Clients → Dailies → Documents → Invoices → Projects → Stock → Tasks → Timecards.

---

### 2. NotificationRegistry Duplicate Key Guard

**File:** `app/Core/Notification/Services/NotificationRegistry.php`
**Method:** `registerDefinitions(array $definitions)`
**Added import:** `Illuminate\Support\Facades\Log`

Before storing a definition, the registry now checks whether the key already exists. If so, it
logs a warning with the conflicting key and the existing label, then skips the duplicate — the
**first registration wins**.

```php
if (array_key_exists($key, $this->definitions)) {
    Log::warning('NotificationRegistry: duplicate key ignored during registerDefinitions.', [
        'key' => $key,
        'existing_label' => $this->definitions[$key]['label'],
    ]);

    continue;
}
```

**Log channel:** `laravel.log` / Telescope (default Laravel log driver).

---

### 3. PermissionRegistry Duplicate Key Guard

**File:** `app/Core\User\Services\PermissionRegistry.php`
**Method:** `registerPermissions(array $definitions)`
**Added import:** `Illuminate\Support\Facades\Log`

Same first-wins pattern as the notification registry. The composite key is `resource.action`
(e.g., `tasks.create`).

```php
if (array_key_exists($key, $this->permissions)) {
    Log::warning('PermissionRegistry: duplicate key ignored during registerPermissions.', [
        'key' => $key,
        'existing_label' => $this->permissions[$key]['label'],
    ]);

    continue;
}
```

---

## Domain Boot Independence Rule

From this point forward, all domain service provider `boot()` methods **must not assume** any other
domain has already finished booting. Concrete requirements:

- Do not resolve another domain's models or services inside `boot()` without wrapping in
  `$this->app->booted()`.
- Do not `Gate::policy()` using another domain's model class unless that model's provider is a
  Core provider (which boots before domains).
- Cross-domain event listeners and observers should be registered via `app->booted()` or deferred
  to a dedicated Integration service provider if the coupling is unavoidable.

**Correct pattern for cross-domain deferred work:**

```php
public function boot(): void
{
    $this->app->booted(function (): void {
        // Safe: all domains have booted by this point.
        Event::listen(SomeOtherDomainEvent::class, MyDomainListener::class);
    });
}
```

---

## Test Coverage Checklist

These tests should be added to protect these guarantees going forward:

| Test class | Test name | Asserts |
|---|---|---|
| `NotificationRegistryTest` | `it_ignores_duplicate_keys_and_logs_warning` | First registration kept; `Log::warning` called with correct context |
| `NotificationRegistryTest` | `it_registers_definitions_from_multiple_domains_without_collision` | All unique keys from timecards/tasks/projects are present |
| `PermissionRegistryTest` | `it_ignores_duplicate_keys_and_logs_warning` | First registration kept; `Log::warning` called with correct context |
| `PermissionRegistryTest` | `it_registers_permissions_from_multiple_domains_without_collision` | All unique resource.action keys from all domains are present |
| `DomainServiceProviderTest` | `it_discovers_providers_in_alphabetical_order` | Order of registered domains matches alphabetically sorted folder names |
| `DomainServiceProviderTest` | `it_skips_itself_during_discovery` | `DomainServiceProvider` is not re-registered |

---

## Files Changed

| File | Change |
|---|---|
| `app/Domains/Providers/DomainServiceProvider.php` | Added `sort($providerFiles)` |
| `app/Core/Notification/Services/NotificationRegistry.php` | Added duplicate-key guard + Log import |
| `app/Core/User/Services/PermissionRegistry.php` | Added duplicate-key guard + Log import |

---

## Related Tickets

1. Platform: Deterministic Domain Provider Load Order
2. Platform: Notification Registry Duplicate Key Protection
3. Platform: Permission Registry Duplicate Key Protection
4. Platform: Domain Boot Order Independence Guidelines and Tests
