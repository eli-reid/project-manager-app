<?php

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
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('notifications.edit'))
        ->assertSuccessful()
        ->assertSee('Notifications')
        ->assertSee('Timecard Approved');
});

it('persists notification preference updates through the livewire settings component', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $user = User::factory()->create(['is_admin' => false]);
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

    $user = User::factory()->create(['is_admin' => false]);
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
