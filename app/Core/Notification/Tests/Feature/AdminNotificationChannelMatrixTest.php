<?php

use App\Core\Notification\Livewire\Admin\ChannelMatrix;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Settings\Livewire\SettingsEditor;
use App\Core\Settings\Models\SettingsSqlite;
use App\Core\User\Models\User;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use Livewire\Livewire;

it('does not render the admin notification channel matrix before the notifications tab is selected', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertSuccessful()
        ->assertDontSee('Notification Channel Rules');
});

it('renders the admin notification channel matrix when the notifications tab is selected', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);

    Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'notifications')
        ->assertSee('Enable Notifications')
        ->assertSee('Default Notification Channels')
        ->assertSee('Notification Channel Rules')
        ->assertSee('Timecards')
        ->assertSee('Projects')
        ->assertSee('Tasks')
        ->assertSee('Timecard Approved');
});

it('hides raw allowed-channel json settings when the notifications tab is selected', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);

    $component = Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'notifications')
        ->assertDontSee('Timecard Approved Allowed Channels')
        ->assertDontSee(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED));

    expect($component->get('settingsMetadata'))->toBe([]);
});

it('persists notification tab global controls and admin channel rules through the livewire matrix', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $formKey = ChannelMatrix::notificationFormKey(TimecardNotificationDefinitions::APPROVED);

    $this->actingAs($admin);

    Livewire::test(ChannelMatrix::class)
        ->set('notificationsEnabled', false)
        ->set('defaultChannels.database', true)
        ->set('defaultChannels.mail', false)
        ->set('defaultChannels.sms', true)
        ->set('defaultChannels.push', false)
        ->set('channels.'.$formKey.'.database', true)
        ->set('channels.'.$formKey.'.mail', false)
        ->set('channels.'.$formKey.'.sms', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notification-channel-matrix-saved');

    expect(SettingsSqlite::query()->where('key', 'notifications.enabled')->value('value'))
        ->toBe('false');

    expect(json_decode((string) SettingsSqlite::query()->where('key', 'notifications.default_channels')->value('value'), true, 512, JSON_THROW_ON_ERROR))
        ->toBe(['database', 'sms']);

    $storedValue = SettingsSqlite::query()
        ->where('key', NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED))
        ->value('value');

    expect(json_decode((string) $storedValue, true, 512, JSON_THROW_ON_ERROR))
        ->toBe(['database', 'sms']);
});

it('forbids non-admin users from using the admin notification channel matrix', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user);

    Livewire::test(ChannelMatrix::class)
        ->assertForbidden();
});
