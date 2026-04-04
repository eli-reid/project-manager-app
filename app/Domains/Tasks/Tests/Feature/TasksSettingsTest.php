<?php

use App\Core\Settings\Facades\Settings;
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
    Settings::set('tasks.max_task_depth', '6');

    $synchronizer = app(DomainSettingsSynchronizer::class);
    $synchronizer->sync();

    $definitions = collect($synchronizer->loadDefinitions());
    $taskDepthDefinition = $definitions->firstWhere('key', 'tasks.max_task_depth');

    $value = Settings::get('tasks.max_task_depth', '2')->toString();

    expect($taskDepthDefinition)->not->toBeNull();
    expect((string) ($taskDepthDefinition['default_value'] ?? ''))->toBe('2');
    expect($value)->toBe('6');
});
