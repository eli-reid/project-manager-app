<?php

namespace App\PlugIns\Services;

use App\Core\Identity\Contracts\PluginUserResolver;
use App\Core\Identity\DTO\UserDTO;
use App\PlugIns\Contracts\PluginContext;
use App\PlugIns\Contracts\PluginDataGateway;
use Illuminate\Contracts\Auth\Access\Gate;

class PluginExecutionContext implements PluginContext
{
    public function __construct(
        private readonly string $pluginId,
        private readonly PluginUserResolver $userResolver,
        private readonly PluginDataGateway $dataGateway,
        private readonly Gate $gate,
    ) {}

    public function pluginId(): string
    {
        return $this->pluginId;
    }

    public function currentUser(): ?UserDTO
    {
        return $this->userResolver->currentUser();
    }

    public function findUser(string $userId): ?UserDTO
    {
        return $this->userResolver->find($userId);
    }

    /**
     * @param  list<string>  $userIds
     * @return list<UserDTO>
     */
    public function findUsers(array $userIds): array
    {
        return $this->userResolver->findMany($userIds);
    }

    public function can(string $ability, mixed $arguments = []): bool
    {
        return $this->gate->check($ability, $arguments);
    }

    public function requestData(string $resourceKey, array $parameters = []): mixed
    {
        return $this->dataGateway->requestFor($this->pluginId, $resourceKey, $parameters);
    }
}
