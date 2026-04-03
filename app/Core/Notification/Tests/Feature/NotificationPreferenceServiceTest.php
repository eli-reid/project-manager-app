<?php

use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\Notification\Services\NotificationPreferenceService;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\User\Models\User;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;

it('resolves default channels when no user preference exists', function (): void {
    settings()->set('notifications.enabled', 'true');
    settings()->set('notifications.default_channels', '["mail", "database"]');
    settings()->set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database", "sms"]');

    $user = User::factory()->create(['is_admin' => false]);
    $channels = app(NotificationPreferenceService::class)->resolveChannels(
        $user,
        TimecardNotificationDefinitions::APPROVED,
        ['mail', 'database', SmsChannel::class],
    );

    expect($channels)
        ->toContain('mail')
        ->toContain('database')
        ->not->toContain(SmsChannel::class);
});

it('respects user channel preference overrides', function (): void {
    settings()->set('notifications.enabled', 'true');
    settings()->set('notifications.default_channels', '["mail", "database"]');
    settings()->set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database", "sms"]');

    $user = User::factory()->create(['is_admin' => false]);

    UserNotificationPreference::query()->create([
        'user_id' => $user->id,
        'notification_key' => TimecardNotificationDefinitions::APPROVED,
        'channel' => 'mail',
        'enabled' => false,
    ]);

    $channels = app(NotificationPreferenceService::class)->resolveChannels(
        $user,
        TimecardNotificationDefinitions::APPROVED,
        ['mail', 'database', SmsChannel::class],
    );

    expect($channels)
        ->not->toContain('mail')
        ->toContain('database');
});

it('returns no channels when notifications are globally disabled', function (): void {
    settings()->set('notifications.enabled', 'false');
    settings()->set('notifications.default_channels', '["mail", "database"]');
    settings()->set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database", "sms"]');

    $user = User::factory()->create(['is_admin' => false]);
    $channels = app(NotificationPreferenceService::class)->resolveChannels(
        $user,
        TimecardNotificationDefinitions::APPROVED,
        ['mail', 'database', SmsChannel::class],
    );

    expect($channels)->toBe([]);
});

it('resolves only admin-allowed channels for a notification type', function (): void {
    settings()->set('notifications.enabled', 'true');
    settings()->set('notifications.default_channels', '["mail", "database", "sms"]');
    settings()->set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["database"]');

    $user = User::factory()->create(['is_admin' => false]);
    $channels = app(NotificationPreferenceService::class)->resolveChannels(
        $user,
        TimecardNotificationDefinitions::APPROVED,
        ['mail', 'database', SmsChannel::class],
    );

    expect($channels)
        ->toContain('database')
        ->not->toContain('mail')
        ->not->toContain(SmsChannel::class);
});

it('marks admin-disabled channels as unsupported in the preference matrix', function (): void {
    settings()->set('notifications.enabled', 'true');
    settings()->set('notifications.default_channels', '["mail", "database", "sms"]');
    settings()->set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["database"]');

    $user = User::factory()->create(['is_admin' => false]);
    $definition = collect(app(NotificationPreferenceService::class)->preferenceMatrixFor($user))
        ->firstWhere('key', TimecardNotificationDefinitions::APPROVED);

    expect($definition)->not->toBeNull();

    $channels = collect($definition['channels'])->keyBy('key');

    expect($channels['database']['supported'])->toBeTrue()
        ->and($channels['mail']['supported'])->toBeFalse()
        ->and($channels['sms']['supported'])->toBeFalse();
});
