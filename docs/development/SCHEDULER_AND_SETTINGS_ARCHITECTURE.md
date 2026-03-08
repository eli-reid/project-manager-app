# Settings and Scheduler: Human-Readable Guide

This is the practical guide for using the two systems:
- Settings: store and read app/domain configuration.
- Scheduler: define recurring tasks and run domain jobs on a schedule.

If you only remember one thing, remember this:
- Use `setting()` and `settings()` for settings.
- Register scheduler tasks in a provider, and let the scheduler queue the work.

## Quick Start

### Settings in 30 seconds
1. Add a setting definition in a domain config file.
2. Read it with `setting('your.key', $default)`.
3. Update it with `settings()->set('your.key', $value)`.

### Scheduler in 30 seconds
1. Create a task class that implements `SchedulableTask`.
2. Register it in a provider with `TaskTypeRegistry::register(...)`.
3. Enable/configure it in admin at `/admin/tasks`.
4. Make sure queue workers are running for `scheduled-tasks`.

## Settings: How to Use It

### What it is
Settings are stored in a dedicated SQLite file (`settings.data`) and loaded early in app boot. This means settings are available even if the main app database is not fully ready.

### Where to define settings
The app automatically discovers settings from:
- `app/config/settings.php`
- `app/Core/*/config/settings.php`
- `app/Domains/*/config/settings.php`

### Example: add a domain setting
Create `app/Domains/Reports/config/settings.php`:

```php
<?php

return [
    [
        'key' => 'reports.weekly.enabled',
        'value' => false,
        'default_value' => false,
        'display_name' => 'Weekly Reports Enabled',
        'description' => 'Enable weekly report generation.',
        'type' => 'boolean',
        'group' => 'reports',
        'order' => 10,
        'is_public' => false,
        'is_visible' => true,
        'is_required' => false,
        'encrypted' => false,
    ],
];
```

### Read and write settings in code
Use helpers instead of direct model queries.

```php
<?php

$enabled = setting('reports.weekly.enabled', false);

if ($enabled) {
    settings()->set('reports.last_checked_at', now()->toDateTimeString());
}
```

Other useful helpers:
- `setting_int('key', 0)`
- `setting_bool('key', false)`
- `setting_json('key', [])`

### What happens at boot
During boot, `DomainSettingsSynchronizer` checks whether settings definition files changed:
- If changed, it syncs new/updated definitions into the settings table.
- If unchanged, it skips sync for speed.

Important behavior:
- Missing keys are created.
- Existing metadata is updated.
- Existing values are not overwritten unless empty/null (or explicit overwrite mode is used).

### Dev mode shortcut
In local/dev/testing, you can force settings to read from `.env`:

```env
SETTINGS_USE_ENV_IN_DEV=true
```

Configured by `config/settings-db.php`.

## Scheduler: How to Use It

### What it is
The scheduler is domain-agnostic. Core scheduler code only decides what is due and puts jobs on the queue. Domain code does the real work.

### Runtime flow (plain English)
1. `routes/console.php` runs scheduler every minute.
2. `SchedulerService` finds due tasks.
3. Each due task dispatches `ProcessScheduledTaskJob` to queue `scheduled-tasks`.
4. The job resolves your task handler class from `TaskTypeRegistry`.
5. Your handler's `dispatchJob()` runs domain logic (usually by dispatching domain jobs).
6. Task run metadata is updated (`last_run_at`, `next_run_at`, etc.).

### Step 1: create a schedulable task class

```php
<?php

namespace App\Domains\Reports\Scheduler;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\Contracts\SchedulableTask;

class ReportsTask implements SchedulableTask
{
    public function __construct(public ScheduledTask $task) {}

    public function dispatchJob(): void
    {
        // Dispatch the real domain work here.
        // Example: GenerateScheduledReportsJob::dispatch($this->task->id);
    }
}
```

### Step 2: register task in a provider

```php
<?php

use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Domains\Reports\Scheduler\ReportsTask;

public function boot(): void
{
    app(TaskTypeRegistry::class)->register('reports.generate', ReportsTask::class, [
        'name' => 'Generate Reports',
        'description' => 'Generate scheduled reports for configured recipients.',
        'schedule_type' => 'daily',
        'time' => '06:00:00',
        'timezone' => 'America/New_York',
        'is_active' => true,
        'is_enabled' => false,
        'task_config' => [
            'report_type' => 'weekly_summary',
        ],
    ]);
}
```

What this registration does:
- Links `feature_type` (`reports.generate`) to your task class.
- Provides default row data for `scheduled_tasks` if it does not exist yet.

### Step 3: enable and tune it
Go to `/admin/tasks` and configure:
- active/enabled state
- schedule pattern/timezone
- optional task config

### Step 4: run workers
Scheduler queues work; workers execute it.

If workers are not running, due tasks will be queued but not processed.

## Common Questions

### Are `next_run_at` and `last_run_at` still used?
Yes. They are still part of scheduler state and used for due detection and run history.

### Why not run domain code directly in `SchedulerService`?
To keep scheduler core generic. Domains own their own behavior; scheduler only coordinates queueing.

### Is sync safe to run multiple times?
Yes. Both settings sync and task-definition sync are idempotent by design.

## Key Files

Settings:
- `app/Core/Settings/Providers/SettingServiceProvider.php`
- `app/Core/Settings/Services/DomainSettingsSynchronizer.php`
- `app/Core/Settings/Helpers/SettingsHelpers.php`
- `config/settings-db.php`

Scheduler:
- `app/Core/Scheduler/Providers/SchedulerServiceProvider.php`
- `app/Core/Scheduler/Services/SchedulerService.php`
- `app/Core/Scheduler/Jobs/ProcessScheduledTaskJob.php`
- `app/Core/Scheduler/Services/TaskTypeRegistry.php`
- `app/Core/Scheduler/Services/TaskDefinitionSyncService.php`
- `routes/console.php`
