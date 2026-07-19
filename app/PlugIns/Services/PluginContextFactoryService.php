<?php

namespace App\PlugIns\Services;

use App\Core\Identity\Contracts\PluginUserResolver;
use App\PlugIns\Contracts\PluginContext;
use App\PlugIns\Contracts\PluginContextFactory;
use App\PlugIns\Contracts\PluginDataGateway;
use Illuminate\Contracts\Auth\Access\Gate;

class PluginContextFactoryService implements PluginContextFactory
{
    public function __construct(
        private readonly PluginUserResolver $userResolver,
        private readonly PluginDataGateway $dataGateway,
        private readonly Gate $gate,
    ) {}

    public function make(string $pluginId): PluginContext
    {
        return new PluginExecutionContext(
            pluginId: $pluginId,
            userResolver: $this->userResolver,
            dataGateway: $this->dataGateway,
            gate: $this->gate,
        );
    }
}
