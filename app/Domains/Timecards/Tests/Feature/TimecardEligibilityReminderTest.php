<?php

use App\Core\Identity\Models\User;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardRequiredUser;
use App\Domains\Timecards\Notifications\MissingTimecardReminder;
use App\Domains\Timecards\Notifications\TimecardReminderNotification;
use Illuminate\Support\Facades\Notification;

function createScheduledTask(array $config = []): ScheduledTask
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
        ], $config),
    ]);
}

it('sends reminder for existing draft timecard if user is required', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    TimecardRequiredUser::factory()->create(['user_id' => $user->id, 'reminders_enabled' => true]);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_ending' => now()->subDay()->endOfWeek(),
    ]);

    $scheduledTask = createScheduledTask();
    (new TimecardReminderTask($scheduledTask))->dispatchJob();

    Notification::assertSentTo($user, TimecardReminderNotification::class);
});

it('does not send reminder for draft timecard if user is not required', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    // Don't mark user as required

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_ending' => now()->subDay()->endOfWeek(),
    ]);

    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    Notification::assertNotSentTo($user, TimecardReminderNotification::class);
});

it('sends missing timecard reminder for required user with no submitted timecard', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    TimecardRequiredUser::factory()->create(['user_id' => $user->id, 'reminders_enabled' => true]);

    // No timecard created for this user

    $targetDate = now()->startOfDay();
    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    Notification::assertSentTo($user, MissingTimecardReminder::class);
});

it('does not send missing reminder if user already has submitted timecard for week', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    TimecardRequiredUser::factory()->create(['user_id' => $user->id, 'reminders_enabled' => true]);

    // Create submitted timecard
    Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'week_starting' => now()->startOfWeek(),
    ]);

    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('respects effective_start_date for timecard requirement', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    // Requirement starts tomorrow
    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => true,
        'effective_start_date' => now()->addDay(),
    ]);

    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    // Should not send reminder yet since requirement hasn't started
    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('respects effective_end_date for timecard requirement', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    // Requirement ended yesterday
    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => true,
        'effective_end_date' => now()->subDay(),
    ]);

    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    // Should not send reminder since requirement has ended
    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('respects reminders_enabled flag', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    TimecardRequiredUser::factory()->create([
        'user_id' => $user->id,
        'reminders_enabled' => false, // Disabled
    ]);

    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('does not send duplicate reminders on same day', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => true]);
    TimecardRequiredUser::factory()->create(['user_id' => $user->id, 'reminders_enabled' => true]);

    // First run
    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    Notification::assertSentToTimes($user, MissingTimecardReminder::class, 1);

    // Clear fake but keep cache
    Notification::fake();

    // Second run same day
    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('does not send missing reminder to inactive users', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_active' => false]);
    TimecardRequiredUser::factory()->create(['user_id' => $user->id, 'reminders_enabled' => true]);

    app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    Notification::assertNotSentTo($user, MissingTimecardReminder::class);
});

it('returns count of sent reminders', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    User::factory(3)->create(['is_active' => true])->each(function (User $user): void {
        TimecardRequiredUser::factory()->create(['user_id' => $user->id, 'reminders_enabled' => true]);
    });

    $result = app(TaskTypeRegistry::class)->resolve('timecard_reminders')
        ->execute(['days_after_week_end' => 0]);

    expect($result)->toBe(3);
});
