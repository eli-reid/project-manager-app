<?php

namespace App\PlugIns\Services;

use App\Core\Identity\Contracts\PluginUserResolver;
use App\PlugIns\Contracts\PluginDataGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use InvalidArgumentException;

class PluginDataGatewayService implements PluginDataGateway
{
    public function __construct(
        private readonly PluginDataRegistry $registry,
        private readonly PluginUserResolver $userResolver,
        private readonly Gate $gate,
    ) {}

    public function requestFor(string $pluginId, string $resourceKey, array $parameters = []): mixed
    {
        $definition = $this->registry->find($resourceKey);

        if ($definition === null) {
            throw new InvalidArgumentException("Plugin data provider [{$resourceKey}] is not registered.");
        }

        $allowedCallers = $definition['allowed_callers'];

        if (! in_array('*', $allowedCallers, true) && ! in_array($pluginId, $allowedCallers, true)) {
            throw new AuthorizationException("Plugin [{$pluginId}] is not allowed to access [{$resourceKey}].");
        }

        $requiredAbility = $definition['required_ability'];

        if ($requiredAbility !== '' && ! $this->gate->check($requiredAbility)) {
            throw new AuthorizationException("The current user is not authorized for [{$resourceKey}].");
        }

        return ($definition['resolver'])(
            $parameters,
            $this->userResolver->currentUser(),
            $pluginId,
        );
    }
}
