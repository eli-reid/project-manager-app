<?php

namespace App\Domains\Addresses\Services;

use App\Core\Identity\Models\User;
use App\Domains\Addresses\Models\Address;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class AddressAccessService
{
    public function canViewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->hasPermission('addresses.view')
            || $user->hasPermission('projects.view')
            || $user->hasPermission('projects.view-all');
    }

    public function canAccessAddress(User $user, Address $address): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->accessibleAddressesQuery($user)
            ->whereKey($address->id)
            ->exists();
    }

    public function accessibleAddressesQuery(User $user): Builder
    {
        if ($user->isAdmin()) {
            return Address::query();
        }

        $projectAddressIds = $this->accessibleProjectAddressIdsQuery($user);

        return Address::query()
            ->where(function (Builder $builder) use ($user, $projectAddressIds): void {
                $builder
                    ->whereHas('users', function (Builder $userAddressQuery) use ($user): void {
                        $userAddressQuery->where('users.id', $user->id);
                    })
                    ->orWhereIn('id', $projectAddressIds);
            });
    }

    private function accessibleProjectAddressIdsQuery(User $user): Builder
    {
        $projectQuery = Project::query()
            ->select('address_id')
            ->whereNotNull('address_id');

        if ($user->isAdmin() || $user->hasPermission('projects.view-all')) {
            return $projectQuery;
        }

        if (! $user->hasPermission('projects.view')) {
            return $projectQuery->whereRaw('1 = 0');
        }

        $activeRoleIds = $user->roles()
            ->where('is_active', true)
            ->pluck('roles.id')
            ->all();

        return $projectQuery->where(function (Builder $builder) use ($activeRoleIds, $user): void {
            $builder
                ->where(function (Builder $unscopedQuery): void {
                    $unscopedQuery
                        ->whereDoesntHave('userAccesses')
                        ->whereDoesntHave('roleAccesses');
                })
                ->orWhere('project_manager_id', $user->id)
                ->orWhereHas('userAccesses', function (Builder $accessQuery) use ($user): void {
                    $accessQuery
                        ->where('user_id', $user->id)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });

            if ($activeRoleIds !== []) {
                $builder->orWhereHas('roleAccesses', function (Builder $accessQuery) use ($activeRoleIds): void {
                    $accessQuery
                        ->whereIn('role_id', $activeRoleIds)
                        ->whereJsonContains('permission_keys', 'projects.view');
                });
            }
        });
    }
}
