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

it('updates payroll leave reset policy from settings editor', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    SettingsSqlite::query()->updateOrCreate(
        ['key' => 'payroll.leave.reset_policy'],
        [
            'value' => 'calendar_year',
            'default_value' => 'calendar_year',
            'display_name' => 'Leave Reset Policy',
            'description' => 'Select when sick and vacation balances reset each cycle.',
            'type' => 'select',
            'group' => 'payroll',
            'options' => json_encode([
                'calendar_year' => 'End of calendar year',
                'hire_date' => 'Hire date anniversary',
            ]),
            'order' => 12,
            'is_public' => false,
            'is_visible' => true,
            'is_required' => false,
            'encrypted' => false,
        ]
    );

    $component = Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'payroll')
        ->assertSet('errorMessage', null);

    $settingsMetadata = $component->get('settingsMetadata');

    $fieldId = collect($settingsMetadata)
        ->search(fn (array $meta): bool => ($meta['setting_key'] ?? null) === 'payroll.leave.reset_policy');

    expect($fieldId)->not->toBeFalse();

    $component
        ->set("formData.{$fieldId}", 'hire_date')
        ->call('updateSetting', $fieldId)
        ->assertSet('errorMessage', null);

    expect(SettingsSqlite::query()->where('key', 'payroll.leave.reset_policy')->value('value'))
        ->toBe('hire_date');
});

it('shows plans sheet number detection as friendly presets', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->artisan('settings:resync-domain')->assertSuccessful();

    $component = Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'plans')
        ->assertSet('errorMessage', null);

    $settingsMetadata = $component->get('settingsMetadata');

    $fieldId = collect($settingsMetadata)
        ->search(fn (array $meta): bool => ($meta['setting_key'] ?? null) === 'plans.sheet_number_pattern');

    expect($fieldId)->not->toBeFalse();

    $meta = $settingsMetadata[$fieldId];

    expect($meta['type'])->toBe('select')
        ->and($meta['display_name'])->toBe('Sheet Number Format')
        ->and($meta['options'])->toContain('Common construction sheets - A 0.05, H 0.01, E1, FA 0.01, A-101')
        ->and($meta['options'])->toContain('Decimal sheets only - A 0.05, H 0.01, E 2.03');
});

it('shows plans title block region as friendly presets', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->artisan('settings:resync-domain')->assertSuccessful();

    $component = Livewire::test(SettingsEditor::class)
        ->call('loadSettings', 'plans')
        ->assertSet('errorMessage', null);

    $settingsMetadata = $component->get('settingsMetadata');

    $fieldId = collect($settingsMetadata)
        ->search(fn (array $meta): bool => ($meta['setting_key'] ?? null) === 'plans.title_block_region');

    expect($fieldId)->not->toBeFalse();

    $meta = $settingsMetadata[$fieldId];

    expect($meta['type'])->toBe('select')
        ->and($meta['display_name'])->toBe('Title Block Region')
        ->and($meta['options'])->toContain('Bottom-right title and number')
        ->and($meta['options'])->toContain('Bottom-right title and number (legacy)')
        ->and($meta['options'])->toContain('Full page');
});
