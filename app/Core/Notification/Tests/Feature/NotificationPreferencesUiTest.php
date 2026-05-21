<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Notification\Livewire\Settings\Preferences;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use Livewire\Livewire;

beforeEach(function (): void {
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["mail", "database", "sms"]');
});

it('registers the notification settings route for authenticated users', function (): void {
    expect(route('notifications.edit', absolute: false))->toBe('/settings/notifications');
});

it('renders the notification settings page for authenticated users', function (): void {
    $user = notificationPreferenceUserWithPermissions(['timecards.view']);

    $this->actingAs($user)
        ->get(route('notifications.edit'))
        ->assertSuccessful()
        ->assertSee('Notifications')
        ->assertSee('Timecard Approved');
});

it('hides notification types outside the users permission scope', function (): void {
    $user = notificationPreferenceUserWithPermissions(['timecards.view']);

    $this->actingAs($user)
        ->get(route('notifications.edit'))
        ->assertSuccessful()
        ->assertSee('Timecard Approved')
        ->assertDontSee('Task Assigned')
        ->assertDontSee('Project Access Granted');
});

it('persists notification preference updates through the livewire settings component', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = notificationPreferenceUserWithPermissions(['timecards.view']);
    $formKey = Preferences::notificationFormKey(TimecardNotificationDefinitions::APPROVED);

    $this->actingAs($user);

    Livewire::test(Preferences::class)
        ->set('preferences.'.$formKey.'.mail', false)
        ->set('preferences.'.$formKey.'.database', true)
        ->set('preferences.'.$formKey.'.sms', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('preferences-saved');

    expect(UserNotificationPreference::query()
        ->where('user_id', $user->id)
        ->where('notification_key', TimecardNotificationDefinitions::APPROVED)
        ->where('channel', 'mail')
        ->value('enabled'))->toBeFalse();

    expect(UserNotificationPreference::query()
        ->where('user_id', $user->id)
        ->where('notification_key', TimecardNotificationDefinitions::APPROVED)
        ->where('channel', 'sms')
        ->value('enabled'))->toBeTrue();
});

it('does not persist channels disabled by admin notification settings', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database", "sms"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["database"]');

    $user = notificationPreferenceUserWithPermissions(['timecards.view']);
    $formKey = Preferences::notificationFormKey(TimecardNotificationDefinitions::APPROVED);

    $this->actingAs($user);

    Livewire::test(Preferences::class)
        ->set('preferences.'.$formKey.'.mail', true)
        ->set('preferences.'.$formKey.'.database', true)
        ->set('preferences.'.$formKey.'.sms', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('preferences-saved');

    expect(UserNotificationPreference::query()
        ->where('user_id', $user->id)
        ->where('notification_key', TimecardNotificationDefinitions::APPROVED)
        ->where('channel', 'database')
        ->value('enabled'))->toBeTrue();

    expect(UserNotificationPreference::query()
        ->where('user_id', $user->id)
        ->where('notification_key', TimecardNotificationDefinitions::APPROVED)
        ->where('channel', 'mail')
        ->exists())->toBeFalse();

    expect(UserNotificationPreference::query()
        ->where('user_id', $user->id)
        ->where('notification_key', TimecardNotificationDefinitions::APPROVED)
        ->where('channel', 'sms')
        ->exists())->toBeFalse();
});

it('requires at least one channel for timecard reminder notifications', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database", "sms"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::REMINDER), '["mail", "database", "sms"]');

    $user = notificationPreferenceUserWithPermissions(['timecards.view']);
    $reminderFormKey = Preferences::notificationFormKey(TimecardNotificationDefinitions::REMINDER);

    $this->actingAs($user);

    Livewire::test(Preferences::class)
        ->set('preferences.'.$reminderFormKey.'.mail', false)
        ->set('preferences.'.$reminderFormKey.'.database', false)
        ->set('preferences.'.$reminderFormKey.'.sms', false)
        ->call('save')
        ->assertHasErrors(['preferences.'.$reminderFormKey])
        ->assertNotDispatched('preferences-saved');
});

/**
 * @param  array<int, string>  $permissions
 */
function notificationPreferenceUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Notification Preference Test Role '.str()->uuid(),
        'description' => 'Role created by notification preference tests.',
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
