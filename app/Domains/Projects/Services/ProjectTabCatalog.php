<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Contracts\ProjectTabInterface;
use App\Domains\Projects\Support\ResolvedProjectTab;

class ProjectTabCatalog
{
    /** @var array<int, class-string<ProjectTabInterface>> */
    private array $registeredTabs = [];

    /**
     * @param  array<int, class-string<ProjectTabInterface>>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            if (! is_string($definition) || $definition === '' || ! class_exists($definition)) {
                continue;
            }

            $tab = app($definition);
            if (! $tab instanceof ProjectTabInterface) {
                continue;
            }

            $this->registeredTabs[] = $definition;
        }

        $this->registeredTabs = array_values(array_unique($this->registeredTabs));
    }

    /**
     * @return array<string, ResolvedProjectTab>
     */
    public function registeredTabs(): array
    {
        $tabs = [];

        foreach ($this->registeredTabs as $className) {
            $tab = app($className);

            if (! $tab instanceof ProjectTabInterface) {
                continue;
            }

            $tabs[$tab->key()] = ResolvedProjectTab::from($tab);
        }

        return $tabs;
    }
}
