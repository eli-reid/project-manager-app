<?php

declare(strict_types=1);

namespace App\Domains\Projects\Contracts;

use App\Domains\Projects\Contracts\ProjectRef;

interface ProjectPluginInterface
{
    /**
     * Unique plugin key
     */
    public function key(): string;

    /**
     * Register plugin capabilities against a project ref
     *
     * @param ProjectRef $project
     * @return void
     */
    public function register(ProjectRef $project): void;
}
