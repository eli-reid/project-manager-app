<?php

namespace App\Domains\Projects\Contracts;

use App\Domains\Projects\Contracts\ProjectRef;

interface ProjectPlugin
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
