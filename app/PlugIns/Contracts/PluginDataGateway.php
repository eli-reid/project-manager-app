<?php

namespace App\PlugIns\Contracts;

interface PluginDataGateway
{
    public function requestFor(string $pluginId, string $resourceKey, array $parameters = []): mixed;
}
