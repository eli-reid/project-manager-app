<?php

namespace App\Core\Identity\Services;

use App\Core\Identity\Contracts\PluginUserResolver;
use App\Core\Identity\DTO\UserDTO;
use App\Core\Identity\Models\User;
use Illuminate\Contracts\Auth\Guard;

class PluginUserResolverService implements PluginUserResolver
{
    public function __construct(
        private readonly Guard $auth,
    ) {}

    public function currentUser(): ?UserDTO
    {
        $user = $this->auth->user();

        if (! $user instanceof User) {
            return null;
        }

        $user->loadMissing('roles:id,name');

        return UserDTO::fromUser($user);
    }

    public function find(string $userId): ?UserDTO
    {
        $user = User::query()
            ->with('roles:id,name')
            ->find($userId);

        if (! $user instanceof User) {
            return null;
        }

        return UserDTO::fromUser($user);
    }

    /**
     * @param  list<string>  $userIds
     * @return list<UserDTO>
     */
    public function findMany(array $userIds): array
    {
        $normalizedUserIds = collect($userIds)
            ->filter(fn (mixed $userId): bool => is_string($userId) && $userId !== '')
            ->values();

        if ($normalizedUserIds->isEmpty()) {
            return [];
        }

        $usersById = User::query()
            ->whereIn('id', $normalizedUserIds->all())
            ->with('roles:id,name')
            ->get()
            ->keyBy('id');

        return $normalizedUserIds
            ->unique()
            ->map(function (string $userId) use ($usersById): ?UserDTO {
                $user = $usersById->get($userId);

                if (! $user instanceof User) {
                    return null;
                }

                return UserDTO::fromUser($user);
            })
            ->filter()
            ->values()
            ->all();
    }
}
