<?php

namespace App\PlugIns\Contracts;

interface PluginContextFactory
{
    public function make(string $pluginId): PluginContext;
}
