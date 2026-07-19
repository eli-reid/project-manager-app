<?php

namespace App\PlugIns\Contracts;

use App\Core\UI\Dashboard\Data\PanelDefinition;
use App\Core\UI\Navigation\DTO\NavItem;

interface PluginHost
{
    public function registerPanel(PanelDefinition $definition): void;

    public function registerNavigationItem(string $sectionKey, ?string $groupKey, NavItem $item): void;

    /**
     * @param  list<string>  $allowedCallers
     */
    public function registerDataProvider(
        string $key,
        callable $resolver,
        array $allowedCallers = ['*'],
        string $requiredAbility = '',
    ): void;
}
