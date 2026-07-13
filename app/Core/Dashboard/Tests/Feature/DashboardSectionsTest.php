<?php

use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Livewire\Concerns\ResolvesDashboardSections;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;

beforeEach(function (): void {
    $this->registry = new DashboardWidgetRegistry;
});

it('orders enum sections first and appends dynamic sections', function (): void {
    $this->registry->registerDefinitions([
        new WidgetDefinition(key: 'w.primary', component: 'a', section: 'primary', sort: 10),
        new WidgetDefinition(key: 'w.personal', component: 'b', section: 'personal', sort: 10),
        new WidgetDefinition(key: 'w.custom', component: 'c', section: 'custom', sort: 10),
    ]);

    $resolver = new class
    {
        use ResolvesDashboardSections;

        public function callResolve(DashboardWidgetRegistry $r, string $view = 'test')
        {
            return $this->resolveDashboardSections($r, $view);
        }
    };

    $sections = $resolver->callResolve($this->registry);

    $keys = array_values(array_keys($sections));

    expect($keys)->toBe(['primary', 'personal', 'custom']);
    expect($sections['primary'][0]['key'])->toBe('w.primary');
    expect($sections['personal'][0]['key'])->toBe('w.personal');
    expect($sections['custom'][0]['key'])->toBe('w.custom');
});

it('builds dashboard memory probe context summaries', function (): void {
    $this->registry->registerDefinitions([
        new WidgetDefinition(key: 'w.primary', component: 'a', section: 'primary', sort: 10),
        new WidgetDefinition(key: 'w.primary.2', component: 'b', section: 'primary', sort: 20),
        new WidgetDefinition(key: 'w.custom', component: 'c', section: 'custom', sort: 10),
    ]);

    $resolver = new class
    {
        use ResolvesDashboardSections;

        public function callResolve(DashboardWidgetRegistry $registry, string $view = 'test'): array
        {
            return $this->resolveDashboardSections($registry, $view);
        }

        public function callContext(array $sections, DashboardWidgetRegistry $registry): array
        {
            return $this->dashboardMemoryProbeContext($sections, $registry);
        }
    };

    $sections = $resolver->callResolve($this->registry);
    $context = $resolver->callContext($sections, $this->registry);

    expect($context['section_count'])->toBe(2)
        ->and($context['widget_count'])->toBe(3)
        ->and($context['registry_definition_count'])->toBe(3)
        ->and($context['section_widget_counts'])->toBe([
            'primary' => 2,
            'custom' => 1,
        ])
        ->and($context['section_widget_keys'])->toBe([
            'primary' => ['w.primary', 'w.primary.2'],
            'custom' => ['w.custom'],
        ])
        ->and($context['registry_payload']['approx_bytes'])->toBeGreaterThan(0)
        ->and($context['sections_payload']['approx_bytes'])->toBeGreaterThan(0)
        ->and($context['largest_sections'])->toHaveCount(2)
        ->and($context['largest_sections'][0]['key'])->toBe('primary');
});
