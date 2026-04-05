<?php

namespace App\Core\Auth\User\Database\Seeders;

use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_built_in' => true,
                'is_active' => true,
                'password_change_required' => false,
            ]
        );

        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'username' => 'testuser',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_built_in' => false,
                'is_active' => true,
                'password_change_required' => false,
            ]
        );

        $adminRoleId = Role::query()->where('name', Role::BUILT_IN_ADMIN)->value('id');
        $userRoleId = Role::query()->where('name', Role::BUILT_IN_USER)->value('id');

        if ($adminRoleId !== null) {
            $adminUser->roles()->syncWithoutDetaching([$adminRoleId]);
        }

        if ($userRoleId !== null) {
            $testUser->roles()->syncWithoutDetaching([$userRoleId]);
        }
    }
}
