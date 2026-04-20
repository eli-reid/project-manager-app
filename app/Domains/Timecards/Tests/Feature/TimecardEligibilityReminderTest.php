<?php

use App\Core\Identity\Models\User;
use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardRequiredUser;
use App\Domains\Timecards\Notifications\MissingTimecardReminder;
use App\Domains\Timecards\Notifications\TimecardReminderNotification;
use App\Domains\Timecards\Tasks\TimecardReminderTask;
use Illuminate\Support\Facades\Notification;

function createTimecardReminderScheduledTask(array $taskConfig = []): ScheduledTask
{
    $availableTask = AvailableTask::factory()->create([
        'feature_type' => 'timecard_reminders',
        'name' => 'Timecard Reminders',
    ]);

    return ScheduledTask::query()->create([
        'name' => 'Timecard Reminder Task',
        'available_task_id' => $availableTask->id,
        'schedule_type' => 'daily',
        'time' => '09:00:00',
        'timezone' => 'America/New_York',
        'repeat_frequency' => 'daily',
        'repeat_interval' => 1,
        'is_active' => true,
        'is_enabled' => true,
        'task_config' => array_merge([
            'days_after_week_end' => 0,
            'statuses' => [Timecard::STATUS_DRAFT, Timecard::STATUS_REJECTED],
        ], $taskConfig),
    ]);
}

it('sends reminder for existing draft timecard if user is required', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => true,
    ]);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->subWeek()->startOfWeek()->toDateString(),
        'week_ending' => now()->subDay()->toDateString(),
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertSentTo($user, TimecardReminderNotification::class, function (TimecardReminderNotification $notification) use ($timecard): bool {
        return (string) $notification->timecard->id === (string) $timecard->id;
    });
});

it('does not send existing timecard reminder if user is not required', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => now()->subWeek()->startOfWeek()->toDateString(),
        'week_ending' => now()->subDay()->toDateString(),
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertNotSentTo($user, TimecardReminderNotification::class);
});

it('sends missing reminder for required user without submitted or approved timecard', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => true,
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertSentTo($user, MissingTimecardReminder::class);
});

it('does not send missing reminder when submitted timecard exists for current week', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => true,
    ]);

    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'week_starting' => now()->startOfWeek()->toDateString(),
        'week_ending' => now()->startOfWeek()->addDays(6)->toDateString(),
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('does not send missing reminder for inactive required users', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => false]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => true,
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('does not send missing reminder when reminders are disabled', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => false,
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('does not send duplicate missing reminders on the same day', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => true,
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertSentToTimes($user, MissingTimecardReminder::class, 1);

    Notification::fake();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('respects effective date window for required users', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $futureUser = User::factory()->create(['is_active' => true]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $futureUser->id,
        'reminders_enabled' => true,
        'effective_start_date' => now()->addDay(),
    ]);

    $expiredUser = User::factory()->create(['is_active' => true]);

    TimecardRequiredUser::factory()->create([
        'user_id' => $expiredUser->id,
        'reminders_enabled' => true,
        'effective_end_date' => now()->subDay(),
    ]);

    $scheduledTask = createTimecardReminderScheduledTask();

    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertNotSentTo($futureUser, MissingTimecardReminder::class);
    Notification::assertNotSentTo($expiredUser, MissingTimecardReminder::class);
});
