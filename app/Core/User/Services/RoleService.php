<?php

namespace App\Core\User\Services;

use Illuminate\Support\Facades\Cache;
use App\Core\User\Models\Role;
use App\Core\User\Models\Permission;

class RoleService
{
	/**
	 * Get all roles, cached.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getAllRoles()
	{
		return Cache::rememberForever('roles.all', function () {
			return Role::with('permissions')->get();
		});
	}

	/**
	 * Get all permissions, cached.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getAllPermissions()
	{
		return Cache::rememberForever('permissions.all', function () {
			return Permission::all();
		});
	}

	/**
	 * Clear cached roles and permissions.
	 */
	public function clearCache()
	{
		Cache::forget('roles.all');
		Cache::forget('permissions.all');
	}

	/**
	 * Get permissions for a specific role, cached per role.
	 *
	 * @param int $roleId
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getPermissionsForRole($roleId)
	{
		return Cache::rememberForever("role.{$roleId}.permissions", function () use ($roleId) {
			$role = Role::with('permissions')->find($roleId);
			return $role ? $role->permissions : collect();
		});
	}
}
