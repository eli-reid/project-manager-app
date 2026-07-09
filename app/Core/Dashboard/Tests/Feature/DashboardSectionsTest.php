<?php

use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;

beforeEach(function (): void {
    $this->registry = new DashboardWidgetRegistry();
});

it('orders enum sections first and appends dynamic sections', function (): void {
    $this->registry->registerDefinitions([
        new WidgetDefinition(key: 'w.primary', component: 'a', section: 'primary', sort: 10),
        new WidgetDefinition(key: 'w.personal', component: 'b', section: 'personal', sort: 10),
        new WidgetDefinition(key: 'w.custom', component: 'c', section: 'custom', sort: 10),
    ]);

    $resolver = new class {
        use \App\Core\Dashboard\Livewire\Concerns\ResolvesDashboardSections;

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
