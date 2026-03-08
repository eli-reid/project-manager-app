<?php

use App\Core\Settings\Livewire\SettingsEditor;
use App\Core\Settings\Models\SettingsSqlite;
use Livewire\Livewire;

it('updates app timezone without value property validation errors', function () {
    SettingsSqlite::query()->create([
        'key' => 'app.timezone',
        'value' => 'UTC',
        'default_value' => 'UTC',
        'display_name' => 'Timezone',
        'description' => 'Application timezone',
        'type' => 'select',
        'group' => 'app',
        'options' => ['UTC' => 'UTC', 'America/Chicago' => 'America/Chicago'],
        'order' => 1,
        'is_public' => false,
        'is_visible' => true,
        'is_required' => true,
        'encrypted' => false,
    ]);

    Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'app')
        ->set('formData', ['app.timezone' => 'America/Chicago'])
        ->call('updateSetting', 'app.timezone')
        ->assertSet('errorMessage', null);

    expect(SettingsSqlite::query()->where('key', 'app.timezone')->value('value'))
        ->toBe('America/Chicago');
});
