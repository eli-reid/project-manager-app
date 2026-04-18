<?php

use App\Core\Identity\Models\User;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Notifications\TimecardReminderDigestNotification;
use App\Domains\Timecards\Services\TimecardReminderService;
use App\Domains\Timecards\Services\TimecardWeekService;
use App\Domains\Timecards\Tasks\TimecardReminderTask;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

it('registers timecard reminder task type in the scheduler registry', function (): void {
    expect(app(TaskTypeRegistry::class)->resolve('timecard_reminders'))
        ->toBe(TimecardReminderTask::class);
});

it('sends reminder notifications for pending timecards', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $targetWeekEnding = app(TimecardWeekService::class)
        ->weekEndingFor(now()->startOfDay())
        ->toDateString();

    $user = User::factory()->create(['is_active' => true]);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->parse($targetWeekEnding)->subDays(6)->toDateString(),
        'week_ending' => $targetWeekEnding,
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
            'batch_size' => 10,
            'statuses' => [Timecard::STATUS_DRAFT],
        ],
    ]);

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertSentTo($user, TimecardReminderDigestNotification::class, function (TimecardReminderDigestNotification $notification) use ($timecard): bool {
        return $notification->timecards->contains(fn (Timecard $candidate): bool => (string) $candidate->id === (string) $timecard->id);
    });
});

it('does not send duplicate reminders on the same day', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $targetWeekEnding = app(TimecardWeekService::class)
        ->weekEndingFor(now()->startOfDay())
        ->toDateString();

    $user = User::factory()->create(['is_active' => true]);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->parse($targetWeekEnding)->subDays(6)->toDateString(),
        'week_ending' => $targetWeekEnding,
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

    expect(Notification::sent($user, TimecardReminderDigestNotification::class))->toHaveCount(1);
});

it('does not send a reminder when the daily reminder key is already claimed', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $targetWeekEnding = app(TimecardWeekService::class)
        ->weekEndingFor(now()->startOfDay())
        ->toDateString();

    $user = User::factory()->create(['is_active' => true]);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->parse($targetWeekEnding)->subDays(6)->toDateString(),
        'week_ending' => $targetWeekEnding,
        'total_hours' => 8,
    ]);

    Cache::add('timecards.reminder_sent.user.'.$user->id.'.week_ending.'.$targetWeekEnding.'.'.now()->toDateString(), true, now()->endOfDay());

    $sentCount = app(TimecardReminderService::class)->sendPendingReminderNotifications([
        'days_after_week_end' => 0,
        'batch_size' => 10,
        'statuses' => [Timecard::STATUS_DRAFT],
    ]);

    expect($sentCount)->toBe(0)
        ->and(Notification::sent($user, TimecardReminderDigestNotification::class))->toHaveCount(0);
});

it('ignores older pending timecards outside the relevant reminder week', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $targetWeekEnding = app(TimecardWeekService::class)
        ->weekEndingFor(now()->startOfDay())
        ->toDateString();

    $user = User::factory()->create(['is_active' => true]);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->parse($targetWeekEnding)->subDays(13)->toDateString(),
        'week_ending' => now()->parse($targetWeekEnding)->subDays(7)->toDateString(),
        'total_hours' => 8,
    ]);

    $currentWeekTimecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->parse($targetWeekEnding)->subDays(6)->toDateString(),
        'week_ending' => $targetWeekEnding,
        'total_hours' => 8,
    ]);

    $sentCount = app(TimecardReminderService::class)->sendPendingReminderNotifications([
        'days_after_week_end' => 0,
        'batch_size' => 10,
        'statuses' => [Timecard::STATUS_DRAFT],
    ]);

    expect($sentCount)->toBe(1);

    Notification::assertSentTo($user, TimecardReminderDigestNotification::class, function (TimecardReminderDigestNotification $notification) use ($currentWeekTimecard): bool {
        return $notification->timecards->count() === 1
            && (string) $notification->timecards->first()->id === (string) $currentWeekTimecard->id;
    });
});
