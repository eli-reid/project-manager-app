<?php

use App\Core\Identity\Models\User;
use App\Core\Settings\Livewire\SettingsEditor;
use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('updates app timezone without value property validation errors', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $timezoneKey = 'app.timezone.'.Str::lower(Str::random(8));

    SettingsSqlite::query()->create([
        'key' => $timezoneKey,
        'value' => 'UTC',
        'default_value' => 'UTC',
        'display_name' => 'Timezone',
        'description' => 'Application timezone',
        'type' => 'select',
        'group' => 'app',
        'options' => json_encode(['UTC' => 'UTC', 'America/Chicago' => 'America/Chicago']),
        'order' => 1,
        'is_public' => false,
        'is_visible' => true,
        'is_required' => true,
        'encrypted' => false,
    ]);

    $component = Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'app')
        ->assertSet('errorMessage', null);

    $settingsMetadata = $component->get('settingsMetadata');

    $fieldId = collect($settingsMetadata)
        ->search(fn (array $meta): bool => ($meta['setting_key'] ?? null) === $timezoneKey);

    expect($fieldId)->not->toBeFalse();

    $component
        ->assertSet("formData.{$fieldId}", 'UTC')
        ->set("formData.{$fieldId}", 'America/Chicago')
        ->call('updateSetting', $fieldId)
        ->assertSet('errorMessage', null);

    expect(SettingsSqlite::query()->where('key', $timezoneKey)->value('value'))
        ->toBe('America/Chicago');
});

it('updates all settings in a group without cache service initialization errors', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $fromAddressKey = 'mail.from_address.'.Str::lower(Str::random(8));
    $fromNameKey = 'mail.from_name.'.Str::lower(Str::random(8));

    SettingsSqlite::query()->create([
        'key' => $fromAddressKey,
        'value' => 'old@example.com',
        'default_value' => 'old@example.com',
        'display_name' => 'From Address',
        'description' => 'Outgoing from address',
        'type' => 'email',
        'group' => 'mail',
        'options' => null,
        'order' => 1,
        'is_public' => false,
        'is_visible' => true,
        'is_required' => true,
        'encrypted' => false,
    ]);

    SettingsSqlite::query()->create([
        'key' => $fromNameKey,
        'value' => 'Old Name',
        'default_value' => 'Old Name',
        'display_name' => 'From Name',
        'description' => 'Outgoing from name',
        'type' => 'text',
        'group' => 'mail',
        'options' => null,
        'order' => 2,
        'is_public' => false,
        'is_visible' => true,
        'is_required' => true,
        'encrypted' => false,
    ]);

    Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'mail')
        ->set('formData', [
            $fromAddressKey => 'new@example.com',
            $fromNameKey => 'New Name',
        ])
        ->call('updateAllSettings')
        ->assertSet('errorMessage', null);

    expect(SettingsSqlite::query()->where('key', $fromAddressKey)->value('value'))
        ->toBe('new@example.com');

    expect(SettingsSqlite::query()->where('key', $fromNameKey)->value('value'))
        ->toBe('New Name');
});
