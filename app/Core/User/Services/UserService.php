<?php

namespace App\Core\User\Services;

use App\Core\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class UserService
{
	/**
	 * Find a user by ID.
	 *
	 * @param int $id
	 * @return User|null
	 */
	public function findById($id)
	{
		return User::find($id);
	}

	/**
	 * Find a user by email.
	 *
	 * @param string $email
	 * @return User|null
	 */
	public function findByEmail($email)
	{
		return User::where('email', $email)->first();
	}

	/**
	 * Update user profile fields.
	 *
	 * @param User $user
	 * @param array $data
	 * @return User
	 */
	public function updateProfile(User $user, array $data)
	{
		$user->fill($data);
		$user->save();
		return $user;
	}

	/**
	 * Change user password.
	 *
	 * @param User $user
	 * @param string $newPassword
	 * @return void
	 */
	public function changePassword(User $user, $newPassword)
	{
		$user->password = Hash::make($newPassword);
		$user->save();
	}

	/**
	 * Activate a user.
	 *
	 * @param User $user
	 * @return void
	 */
	public function activate(User $user)
	{
		$user->active = true;
		$user->save();
	}

	/**
	 * Deactivate a user.
	 *
	 * @param User $user
	 * @return void
	 */
	public function deactivate(User $user)
	{
		$user->active = false;
		$user->save();
	}

	/**
	 * Get all active users.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getActiveUsers()
	{
		return User::where('active', true)->get();
	}

	/**
	 * Get all users (optionally cached).
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getAllUsers()
	{
		return Cache::remember('users.all', 3600, function () {
			return User::all();
		});
	}
    	/**
	 * Get a user with roles, cached per user.
	 *
	 * @param int $userId
	 * @return User|null
	 */
	public function getUserWithRoles($userId)
	{
		return Cache::rememberForever("user.{$userId}.with_roles", function () use ($userId) {
			return User::with('roles')->find($userId);
		});
	}
}
