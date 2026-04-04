<?php

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\DomainSettingsSynchronizer;

it('defines default task depth settings keys', function (): void {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();

    $keys = collect($definitions)
        ->pluck('key')
        ->all();

    expect($keys)->toContain('tasks.max_category_depth');
    expect($keys)->toContain('tasks.max_task_depth');
});

it('loads task settings definitions from the settings registry', function (): void {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();

    expect($definitions)->not->toBeEmpty();
    expect(collect($definitions)->pluck('key')->all())->toContain('tasks.max_category_depth');
    expect(collect($definitions)->pluck('key')->all())->toContain('tasks.max_task_depth');
});

it('syncs task settings without overwriting existing values by default', function (): void {
    SettingsSqlite::query()->updateOrCreate(
        ['key' => 'tasks.max_task_depth'],
        [
            'value' => '6',
            'default_value' => '6',
            'display_name' => 'Maximum Task Depth',
            'description' => 'Maximum allowed nesting depth for parent-child task chains.',
            'type' => 'number',
            'group' => 'tasks',
            'options' => null,
            'order' => 2,
            'is_public' => false,
            'is_visible' => true,
            'is_required' => false,
            'encrypted' => false,
        ]
    );

    app(DomainSettingsSynchronizer::class)->sync();

    $setting = SettingsSqlite::query()->where('key', 'tasks.max_task_depth')->first();

    expect($setting)->not->toBeNull();
    expect($setting?->value)->toBe('6');
    expect($setting?->default_value)->toBe('2');
});
