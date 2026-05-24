#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\DB;

// Test configuration
$testUsers = [
    'admin' => ['email' => 'admin.test@example.com', 'expected_access' => true],
    'regular' => ['email' => 'regular.user@example.com', 'expected_access' => false],
    'inactive' => ['email' => 'inactive.user@example.com', 'expected_access' => false],
];

$adminEndpoints = [
    '/admin/users',
    '/admin/settings',
    '/admin/projects',
    '/admin/clients',
    '/admin/stock-orders',
    '/admin/timecards',
    '/admin/documents',
    '/admin/announcements',
];

echo "\n========================================\n";
echo "SESSION 4: ADMIN ENDPOINT ACCESS TESTING\n";
echo "========================================\n\n";

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test_count' => 0,
    'passed' => 0,
    'failed' => 0,
    'tests' => [],
];

// Test each user with each endpoint
foreach ($testUsers as $userType => $userData) {
    $user = User::where('email', $userData['email'])->first();
    
    if (!$user) {
        echo "❌ User not found: {$userData['email']}\n";
        continue;
    }

    echo "Testing user: {$userData['email']} (is_admin: {$user->is_admin}, is_active: {$user->is_active})\n";
    echo "─────────────────────────────────────────────────────────────────\n";

    foreach ($adminEndpoints as $endpoint) {
        $results['test_count']++;
        
        // Check if user should have access
        $shouldHaveAccess = $user->is_admin && $user->is_active;
        
        echo "  {$endpoint}";
        echo str_repeat(' ', 30 - strlen($endpoint));
        echo "→ Expected: " . ($shouldHaveAccess ? 'ALLOW (200)' : 'DENY (403/302)');
        echo "\n";

        $results['tests'][] = [
            'user_type' => $userType,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'is_active' => $user->is_active,
            'endpoint' => $endpoint,
            'should_allow' => $shouldHaveAccess,
            'tested' => true,
            'status' => 'PENDING_BROWSER_TEST',
        ];
    }
    
    echo "\n";
}

echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n\n";

echo "Total Tests to Execute: {$results['test_count']}\n";
echo "Test Users: " . count($testUsers) . "\n";
echo "Admin Endpoints: " . count($adminEndpoints) . "\n\n";

echo "Expected Results:\n";
echo "  ✅ Admin user: Should access all endpoints (200 OK)\n";
echo "  ❌ Regular user: Should be denied (403 Forbidden or 302 Redirect)\n";
echo "  ❌ Inactive user: Should be denied (403 Forbidden or 302 Redirect)\n\n";

// Save results to JSON for tracking
$jsonFile = 'session_4_admin_endpoint_tests.json';
file_put_contents($jsonFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Results saved to: {$jsonFile}\n";
echo "\n✅ Test plan generated. Now execute browser tests manually with each user.\n";

// Display test command
echo "\nTo manually test, use these URLs in browser:\n";
foreach ($adminEndpoints as $endpoint) {
    echo "  - https://project-manager-app.test{$endpoint}\n";
}

echo "\nTest with each user:\n";
echo "  1. Admin Test: admin.test@example.com / FuzzTestPass123!\n";
echo "  2. Regular User: regular.user@example.com / FuzzTestPass123!\n";
echo "  3. Inactive User: inactive.user@example.com / FuzzTestPass123!\n";
