<?php

namespace App\Core\UI\Dashboard\Services;

use App\Core\UI\Dashboard\Data\PanelTabGroupDefinition;
use App\Support\Contracts\ProvidesDomainNavbar;
use Illuminate\Support\Facades\Log;

class DashboardPanelTabGroupRegistry
{
    /**
     * @var array<string, array{key: string, panel_keys: array<int, string>, navbar_provider: class-string<ProvidesDomainNavbar>}>
     */
    private array $definitions = [];

    /**
     * @param  array<int, PanelTabGroupDefinition>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            if (! $definition instanceof PanelTabGroupDefinition) {
                continue;
            }

            if ($definition->key === '' || $definition->panelKeys === [] || $definition->navbarProvider === '') {
                continue;
            }

            if (array_key_exists($definition->key, $this->definitions)) {
                Log::warning('DashboardPanelTabGroupRegistry: duplicate key ignored during registerDefinitions.', [
                    'key' => $definition->key,
                ]);

                continue;
            }

            $this->definitions[$definition->key] = $definition->toArray();
        }
    }

    /**
     * @return array<int, array{key: string, panel_keys: array<int, string>, navbar_provider: class-string<ProvidesDomainNavbar>}>
     */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    /**
     * @return array{key: string, panel_keys: array<int, string>, navbar_provider: class-string<ProvidesDomainNavbar>}|null
     */
    public function findByPanelKey(string $panelKey): ?array
    {
        foreach ($this->definitions as $definition) {
            if (in_array($panelKey, $definition['panel_keys'], true)) {
                return $definition;
            }
        }

        return null;
    }
}
