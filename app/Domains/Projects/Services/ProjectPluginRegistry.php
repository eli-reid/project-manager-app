<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Contracts\ProjectPlugin;
use App\Domains\Projects\Contracts\ProjectRef;

class ProjectPluginRegistry
{
    /** @var array<string, ProjectPlugin[]> */
    private array $plugins = [];

    public function registerPlugin(ProjectPlugin $plugin): void
    {
        $this->plugins[$plugin->key()][] = $plugin;
    }

    public function pluginsFor(string $key): array
    {
        return $this->plugins[$key] ?? [];
    }

    public function registerForProject(ProjectRef $project): void
    {
        foreach ($this->plugins as $key => $plugins) {
            foreach ($plugins as $plugin) {
                $plugin->register($project);
            }
        }
    }
}
