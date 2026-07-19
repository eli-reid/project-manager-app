<?php

namespace App\PlugIns\Contracts;

use App\Core\Identity\DTO\UserDTO;

interface PluginContext
{
    public function pluginId(): string;

    public function currentUser(): ?UserDTO;

    public function findUser(string $userId): ?UserDTO;

    /**
     * @param  list<string>  $userIds
     * @return list<UserDTO>
     */
    public function findUsers(array $userIds): array;

    public function can(string $ability, mixed $arguments = []): bool;

    public function requestData(string $resourceKey, array $parameters = []): mixed;
}
