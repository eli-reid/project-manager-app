<?php

namespace Database\Seeders;

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
        User::updateOrCreate(
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

        User::updateOrCreate(
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
    }
}
