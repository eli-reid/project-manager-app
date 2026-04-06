<?php

use App\Core\Identity\Models\User;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Notifications\TimecardReminderNotification;
use App\Domains\Timecards\Tasks\TimecardReminderTask;
use Illuminate\Support\Facades\Notification;

it('registers timecard reminder task type in the scheduler registry', function (): void {
    expect(app(TaskTypeRegistry::class)->resolve('timecard_reminders'))
        ->toBe(TimecardReminderTask::class);
});

it('sends reminder notifications for pending timecards', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->subWeek()->startOfWeek()->toDateString(),
        'week_ending' => now()->subDay()->toDateString(),
        'total_hours' => 8,
    ]);

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $scheduledTask = ScheduledTask::query()->create([
        'name' => 'Timecard Reminder Task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '09:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'task_config' => [
            'days_after_week_end' => 0,
            'statuses' => [Timecard::STATUS_DRAFT],
        ],
    ]);

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertSentTo($user, TimecardReminderNotification::class, function (TimecardReminderNotification $notification) use ($timecard): bool {
        return (string) $notification->timecard->id === (string) $timecard->id;
    });
});

it('does not send duplicate reminders on the same day', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->subWeek()->startOfWeek()->toDateString(),
        'week_ending' => now()->subDay()->toDateString(),
        'total_hours' => 8,
    ]);

    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    $scheduledTask = ScheduledTask::query()->create([
        'name' => 'Timecard Reminder Task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '09:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
    ]);

    $task = new TimecardReminderTask($scheduledTask);
    $task->dispatchJob();
    $task->dispatchJob();

    expect(Notification::sent($user, TimecardReminderNotification::class))->toHaveCount(1);
});
