<?php

use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    // Use a fresh registry instance for each test to avoid state leakage
    $this->registry = new DashboardWidgetRegistry;
});

it('registers a widget definition', function (): void {
    $this->registry->registerDefinitions([
        new WidgetDefinition(
            key: 'test.widget',
            component: 'test.component',
            section: 'primary',
            sort: 10,
            span: 'half',
            ability: '',
            title: 'Test Widget',
            description: 'A test widget.',
        ),
    ]);

    expect($this->registry->all())->toHaveCount(1)
        ->and($this->registry->all()[0]['key'])->toBe('test.widget')
        ->and($this->registry->all()[0]['component'])->toBe('test.component');
});

it('returns widgets in sort order within a section', function (): void {
    $this->registry->registerDefinitions([
        new WidgetDefinition(key: 'test.b', component: 'b', section: 'primary', sort: 20),
        new WidgetDefinition(key: 'test.a', component: 'a', section: 'primary', sort: 10),
        new WidgetDefinition(key: 'test.c', component: 'c', section: 'primary', sort: 30),
    ]);

    $widgets = $this->registry->forSection('primary');

    expect($widgets)->toHaveCount(3)
        ->and($widgets[0]['key'])->toBe('test.a')
        ->and($widgets[1]['key'])->toBe('test.b')
        ->and($widgets[2]['key'])->toBe('test.c');
});

it('filters widgets by section', function (): void {
    $this->registry->registerDefinitions([
        new WidgetDefinition(key: 'test.primary', component: 'a', section: 'primary', sort: 10),
        new WidgetDefinition(key: 'test.personal', component: 'b', section: 'personal', sort: 10),
        new WidgetDefinition(key: 'test.admin', component: 'c', section: 'admin', sort: 10),
    ]);

    expect($this->registry->forSection('primary'))->toHaveCount(1)
        ->and($this->registry->forSection('primary')[0]['key'])->toBe('test.primary')
        ->and($this->registry->forSection('personal'))->toHaveCount(1)
        ->and($this->registry->forSection('admin'))->toHaveCount(1)
        ->and($this->registry->forSection('operations'))->toHaveCount(0);
});

it('ignores duplicate keys and logs a warning', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->with('DashboardWidgetRegistry: duplicate key ignored during registerDefinitions.', Mockery::on(
            fn (array $context): bool => $context['key'] === 'test.widget'
        ));

    $this->registry->registerDefinitions([
        new WidgetDefinition(key: 'test.widget', component: 'original', section: 'primary', sort: 10),
        new WidgetDefinition(key: 'test.widget', component: 'duplicate', section: 'primary', sort: 20),
    ]);

    expect($this->registry->all())->toHaveCount(1)
        ->and($this->registry->all()[0]['component'])->toBe('original');
});

it('skips non-WidgetDefinition entries', function (): void {
    $this->registry->registerDefinitions([
        ['component' => 'no-key-component', 'section' => 'primary'],
    ]);

    expect($this->registry->all())->toHaveCount(0);
});

it('applies sensible defaults when optional fields are omitted', function (): void {
    $this->registry->registerDefinitions([
        new WidgetDefinition(key: 'test.defaults', component: 'some.component'),
    ]);

    $widget = $this->registry->all()[0];

    expect($widget['section'])->toBe('primary')
        ->and($widget['sort'])->toBe(100)
        ->and($widget['span'])->toBe('third')
        ->and($widget['ability'])->toBe('')
        ->and($widget['title'])->toBe('Test Defaults');
});

it('the announcement widget is registered in the container registry', function (): void {
    /** @var DashboardWidgetRegistry $registry */
    $registry = app(DashboardWidgetRegistry::class);

    $keys = collect($registry->all())->pluck('key')->all();

    expect($keys)->toContain('core.announcements');
});

it('the announcement widget is in the primary section with half span', function (): void {
    /** @var DashboardWidgetRegistry $registry */
    $registry = app(DashboardWidgetRegistry::class);

    $widget = collect($registry->all())->firstWhere('key', 'core.announcements');

    expect($widget)->not->toBeNull()
        ->and($widget['section'])->toBe('primary')
        ->and($widget['span'])->toBe('half');
});
