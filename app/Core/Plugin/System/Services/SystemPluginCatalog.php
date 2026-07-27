<?php

namespace App\Core\PluginSystem\Services;

use Illuminate\Support\Collection;

class SystemPluginCatalog
{
    public function __construct(
        private readonly PluginDiscoveryService $pluginDiscoveryService
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        return $this->pluginDiscoveryService->discoverRegisteredPlugins();
    }
}
