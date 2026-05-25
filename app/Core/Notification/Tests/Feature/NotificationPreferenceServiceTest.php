<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\Notification\Services\NotificationPreferenceService;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use Illuminate\Validation\ValidationException;
use NotificationChannels\WebPush\WebPushChannel;

it('resolves default channels when no user preference exists', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database", "sms"]');

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
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database", "sms"]');

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
    Settings::set('notifications.enabled', 'false');
    Settings::set('notifications.default_channels', '["mail", "database"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database", "sms"]');

    $user = User::factory()->create(['is_admin' => false]);
    $channels = app(NotificationPreferenceService::class)->resolveChannels(
        $user,
        TimecardNotificationDefinitions::APPROVED,
        ['mail', 'database', SmsChannel::class],
    );

    expect($channels)->toBe([]);
});

it('resolves only admin-allowed channels for a notification type', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database", "sms"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["database"]');

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
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database", "sms"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["database"]');

    $user = notificationServiceUserWithPermissions(['timecards.view']);
    $definition = collect(app(NotificationPreferenceService::class)->preferenceMatrixFor($user))
        ->firstWhere('key', TimecardNotificationDefinitions::APPROVED);

    expect($definition)->not->toBeNull();

    $channels = collect($definition['channels'])->keyBy('key');

    expect($channels['database']['supported'])->toBeTrue()
        ->and($channels['mail']['supported'])->toBeFalse()
        ->and($channels['sms']['supported'])->toBeFalse();
});

it('resolves push channel to the package web push channel class', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["push", "database"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["database", "push"]');

    $user = User::factory()->create(['is_admin' => false]);
    $channels = app(NotificationPreferenceService::class)->resolveChannels(
        $user,
        TimecardNotificationDefinitions::APPROVED,
        ['database', 'push'],
    );

    expect($channels)
        ->toContain('database')
        ->toContain(WebPushChannel::class)
        ->not->toContain('push');
});

it('writes an audit log when user preferences are synced', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database"]');

    $user = notificationServiceUserWithPermissions(['timecards.view']);
    $this->actingAs($user);

    app(NotificationPreferenceService::class)->syncPreferences($user, [
        TimecardNotificationDefinitions::APPROVED => [
            'mail' => false,
            'database' => true,
        ],
    ]);

    $auditLog = AuditLog::query()->where('action', 'notifications.preferences.updated')->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->target_type)->toBe($user->getMorphClass())
        ->and($auditLog->target_id)->toBe((string) $user->getKey())
        ->and($auditLog->after)->toBe([
            TimecardNotificationDefinitions::APPROVED => [
                'database' => true,
                'mail' => false,
            ],
        ]);
});

it('throws validation when no channel is selected for timecard reminder notifications', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database", "sms"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::REMINDER), '["mail", "database", "sms"]');

    $user = notificationServiceUserWithPermissions(['timecards.view']);

    expect(fn () => app(NotificationPreferenceService::class)->syncPreferences($user, [
        TimecardNotificationDefinitions::REMINDER => [
            'mail' => false,
            'database' => false,
            'sms' => false,
        ],
    ]))->toThrow(ValidationException::class);
});

/**
 * @param  array<int, string>  $permissions
 */
function notificationServiceUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Notification Service Test Role '.str()->uuid(),
        'description' => 'Role created by notification service tests.',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
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
