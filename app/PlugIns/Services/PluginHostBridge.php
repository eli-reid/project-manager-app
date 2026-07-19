<?php

namespace App\PlugIns\Services;

use App\Core\UI\Dashboard\Data\PanelDefinition;
use App\Core\UI\Dashboard\Services\DashboardPanelRegistry;
use App\Core\UI\Navigation\DTO\NavItem;
use App\Core\UI\Navigation\Services\NavigationManager;
use App\PlugIns\Contracts\PluginHost;

class PluginHostBridge implements PluginHost
{
    public function __construct(
        private readonly DashboardPanelRegistry $panelRegistry,
        private readonly NavigationManager $navigationManager,
        private readonly PluginDataRegistry $dataRegistry,
    ) {}

    public function registerPanel(PanelDefinition $definition): void
    {
        $this->panelRegistry->registerDefinitions([$definition]);
    }

    public function registerNavigationItem(string $sectionKey, ?string $groupKey, NavItem $item): void
    {
        $this->navigationManager->registerItem($sectionKey, $groupKey, $item);
    }

    /**
     * @param  list<string>  $allowedCallers
     */
    public function registerDataProvider(
        string $key,
        callable $resolver,
        array $allowedCallers = ['*'],
        string $requiredAbility = '',
    ): void {
        $this->dataRegistry->register($key, $resolver, $allowedCallers, $requiredAbility);
    }
}
