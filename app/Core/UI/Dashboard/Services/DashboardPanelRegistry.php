<?php

namespace App\Core\UI\Dashboard\Services;

use App\Core\UI\Dashboard\Data\PanelDefinition;
use Illuminate\Support\Facades\Log;

class DashboardPanelRegistry
{
    /**
     * @var array<string, array{key: string, component: string, icon: string, sort: int, ability: string, ability_model: string, label: string, description: string, badge: string, navigation_section_key: string, navigation_section_label: string, navigation_section_order: int, navigation_group: ?string}>
     */
    private array $definitions = [];

    /**
     * @param  array<int, PanelDefinition>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            if (! $definition instanceof PanelDefinition) {
                continue;
            }

            if ($definition->key === '' || $definition->component === '') {
                continue;
            }

            if (array_key_exists($definition->key, $this->definitions)) {
                Log::warning('DashboardPanelRegistry: duplicate key ignored during registerDefinitions.', [
                    'key' => $definition->key,
                    'existing_label' => $this->definitions[$definition->key]['label'],
                ]);

                continue;
            }

            $this->definitions[$definition->key] = $definition->toArray();
        }
    }

    /**
     * @return array<int, array{key: string, component: string, icon: string, sort: int, ability: string, ability_model: string, label: string, description: string, badge: string, navigation_section_key: string, navigation_section_label: string, navigation_section_order: int, navigation_group: ?string}>
     */
    public function all(): array
    {
        return collect($this->definitions)
            ->sortBy([
                ['sort', 'asc'],
                ['label', 'asc'],
            ])
            ->values()
            ->all();
    }
}
