<?php

namespace App\Core\Identity\Contracts;

use App\Core\Identity\DTO\UserDTO;

interface PluginUserResolver
{
    public function currentUser(): ?UserDTO;

    public function find(string $userId): ?UserDTO;

    /**
     * @param  list<string>  $userIds
     * @return list<UserDTO>
     */
    public function findMany(array $userIds): array;
}
