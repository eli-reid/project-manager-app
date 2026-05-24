<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Core\Identity\Models\User;

// Create test users with different permission levels
$testUsers = [
    [
        'first_name' => 'Admin',
        'last_name' => 'Test',
        'email' => 'admin.test@example.com',
        'username' => 'admin.test',
        'is_admin' => true,
        'is_built_in' => false,
        'is_active' => true,
        'password_change_required' => false,
    ],
    [
        'first_name' => 'Regular',
        'last_name' => 'User',
        'email' => 'regular.user@example.com',
        'username' => 'regular.user',
        'is_admin' => false,
        'is_built_in' => false,
        'is_active' => true,
        'password_change_required' => false,
    ],
    [
        'first_name' => 'Inactive',
        'last_name' => 'User',
        'email' => 'inactive.user@example.com',
        'username' => 'inactive.user',
        'is_admin' => false,
        'is_built_in' => false,
        'is_active' => false,
        'password_change_required' => false,
    ],
    [
        'first_name' => 'No',
        'last_name' => 'Admin',
        'email' => 'no.admin@example.com',
        'username' => 'no.admin',
        'is_admin' => false,
        'is_built_in' => false,
        'is_active' => true,
        'password_change_required' => false,
    ],
];

$password = bcrypt('FuzzTestPass123!');
$results = [];

foreach ($testUsers as $userData) {
    $existing = User::where('email', $userData['email'])->first();
    if (!$existing) {
        $user = User::create([
            ...$userData,
            'password' => $password,
            'company_email' => str_replace('@example.com', '@midstatecompany.com', $userData['email']),
        ]);
        $results[] = [
            'action' => 'created',
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'id' => $user->id,
            'is_admin' => $user->is_admin,
        ];
    } else {
        $results[] = [
            'action' => 'exists',
            'first_name' => $existing->first_name,
            'last_name' => $existing->last_name,
            'email' => $existing->email,
            'id' => $existing->id,
        ];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
