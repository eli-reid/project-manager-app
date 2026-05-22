<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Scheduler\Livewire\Admin\Settings\SystemTiming;
use App\Core\Scheduler\Livewire\Admin\Tasks\Form;
use App\Core\Scheduler\Livewire\Admin\Tasks\Index;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Scheduler\Tasks\NoOpTask;
use App\Core\Settings\Models\SettingsSqlite;
use Livewire\Livewire;

it('renders scheduler admin pages for admins', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.scheduler.tasks.index'))
        ->assertSuccessful()
        ->assertSee('Scheduler Tasks')
        ->assertSee('Task Status');

    $this->actingAs($admin)
        ->get(route('admin.scheduler.tasks.create'))
        ->assertSuccessful()
        ->assertSee('Create Scheduler Task');

    $this->actingAs($admin)
        ->get(route('admin.scheduler.settings.index'))
        ->assertSuccessful()
        ->assertSee('Scheduler System Timing')
        ->assertSee('Dispatch Claim Window');
});

it('allows scheduler pages for users with scheduler permissions', function (): void {
    $user = schedulerUserWithPermissions(['scheduler.view', 'scheduler.create']);

    $this->actingAs($user)
        ->get(route('admin.scheduler.tasks.index'))
        ->assertSuccessful()
        ->assertSee('Scheduler Tasks');

    $this->actingAs($user)
        ->get(route('admin.scheduler.tasks.create'))
        ->assertSuccessful()
        ->assertSee('Create Scheduler Task');

    $this->actingAs($user)
        ->get(route('admin.scheduler.settings.index'))
        ->assertSuccessful()
        ->assertSee('Scheduler System Timing');
});

it('forbids scheduler admin pages for users without scheduler permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.scheduler.tasks.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.scheduler.tasks.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.scheduler.settings.index'))
        ->assertForbidden();
});

it('updates scheduler claim window from the scheduler timing ui', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    SettingsSqlite::query()->updateOrCreate(
        ['key' => 'scheduler.claim_window_seconds'],
        [
            'value' => '300',
            'default_value' => '300',
            'display_name' => 'Scheduler Claim Window (seconds)',
            'description' => 'Time window for preventing duplicate scheduled task dispatch.',
            'type' => 'number',
            'group' => 'scheduler',
            'options' => null,
            'order' => 1,
            'is_public' => false,
            'is_visible' => true,
            'is_required' => true,
            'encrypted' => false,
        ]
    );

    $this->actingAs($admin);

    Livewire::test(SystemTiming::class)
        ->assertSet('claimWindowSeconds', 300)
        ->set('claimWindowSeconds', 1200)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('successMessage', 'Scheduler timing settings updated.');

    expect(SettingsSqlite::query()->where('key', 'scheduler.claim_window_seconds')->value('value'))
        ->toBe('1200');
});

it('creates a scheduler task through livewire form', function (): void {
    $user = schedulerUserWithPermissions(['scheduler.create']);
    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $this->actingAs($user);

    Livewire::test(Form::class)
        ->set('name', 'Daily Reminder Task')
        ->set('available_task_id', (string) $availableTask->id)
        ->set('timecard_days_after_week_end', 1)
        ->set('timecard_statuses', 'draft,rejected')
        ->set('timecard_ignore_daily_reminder_limit', true)
        ->set('schedule_type', 'daily')
        ->set('time', '08:30')
        ->set('timezone', 'America/New_York')
        ->set('repeat_frequency', 'daily')
        ->set('repeat_interval', 1)
        ->set('is_active', true)
        ->set('is_enabled', true)
        ->call('save')
        ->assertHasNoErrors();

    $task = ScheduledTask::query()->where('name', 'Daily Reminder Task')->first();

    expect($task)->not->toBeNull()
        ->and($task?->next_run_at)->not->toBeNull()
        ->and($task?->task_config['days_after_week_end'] ?? null)->toBe(1)
        ->and($task?->task_config['statuses'] ?? null)->toBe(['draft', 'rejected'])
        ->and($task?->task_config['ignore_daily_reminder_limit'] ?? null)->toBeTrue();
});

it('defaults new scheduler task to active and enabled', function (): void {
    $user = schedulerUserWithPermissions(['scheduler.create']);

    $this->actingAs($user);

    Livewire::test(Form::class)
        ->assertSet('is_active', true)
        ->assertSet('is_enabled', true);
});

it('can toggle and run tasks from index component', function (): void {
    app(TaskTypeRegistry::class)->register('timecard_reminders', NoOpTask::class, [
        'name' => 'Timecard Reminders',
    ]);

    $user = schedulerUserWithPermissions(['scheduler.view', 'scheduler.toggle', 'scheduler.run']);
    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $task = ScheduledTask::query()->create([
        'name' => 'Test Task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '10:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now()->subMinute(),
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('toggleEnabled', $task->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->is_enabled)->toBeFalse();

    $task->update(['is_enabled' => true]);

    Livewire::test(Index::class)
        ->call('runNow', $task->id)
        ->assertHasNoErrors();

    $task->refresh();

    expect($task->run_count)->toBe(1)
        ->and($task->last_run_at)->not->toBeNull()
        ->and($task->next_run_at)->not->toBeNull();
});

/**
 * @param  array<int, string>  $permissions
 */
function schedulerUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Scheduler Test Role '.str()->uuid(),
        'description' => 'Role created by scheduler policy tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
