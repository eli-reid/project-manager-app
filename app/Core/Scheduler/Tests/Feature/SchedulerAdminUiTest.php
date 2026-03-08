<?php

use App\Core\Scheduler\Jobs\ProcessScheduledTaskJob;
use App\Core\Scheduler\Livewire\Admin\Tasks\Form;
use App\Core\Scheduler\Livewire\Admin\Tasks\Index;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\User\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('renders scheduler admin pages for admins', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.scheduler.tasks.index'))
        ->assertSuccessful()
        ->assertSee('Scheduler Tasks');

    $this->actingAs($admin)
        ->get(route('admin.scheduler.tasks.create'))
        ->assertSuccessful()
        ->assertSee('Create Scheduler Task');
});

it('forbids scheduler admin pages for non-admin users', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.scheduler.tasks.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.scheduler.tasks.create'))
        ->assertForbidden();
});

it('creates a scheduler task through livewire form', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(Form::class)
        ->set('name', 'Daily Reminder Task')
        ->set('feature_type', 'timecard_reminders')
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
        ->and($task?->next_run_at)->not->toBeNull();
});

it('can toggle and run tasks from index component', function (): void {
    Queue::fake();

    $admin = User::factory()->create(['is_admin' => true]);

    $task = ScheduledTask::query()->create([
        'name' => 'Test Task',
        'feature_type' => 'timecard_reminders',
        'schedule_type' => 'daily',
        'time' => '10:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'next_run_at' => now()->subMinute(),
    ]);

    $this->actingAs($admin);

    Livewire::test(Index::class)
        ->call('toggleEnabled', $task->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->is_enabled)->toBeFalse();

    $task->update(['is_enabled' => true]);

    Livewire::test(Index::class)
        ->call('runNow', $task->id)
        ->assertHasNoErrors();

    Queue::assertPushed(ProcessScheduledTaskJob::class, function (ProcessScheduledTaskJob $job) use ($task): bool {
        return $job->taskId === (string) $task->id;
    });
});
