#!/usr/bin/env php
<?php
/**
 * Session 4 - Admin Endpoint Access Control Testing
 * Tests each user type against admin endpoints using real HTTP requests
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function runAllTests()
    {
        $testUsers = [
            'admin' => [
                'email' => 'admin.test@example.com',
                'password' => 'FuzzTestPass123!',
                'is_admin' => true,
                'is_active' => true,
                'expected_access' => true,
            ],
            'regular' => [
                'email' => 'regular.user@example.com',
                'password' => 'FuzzTestPass123!',
                'is_admin' => false,
                'is_active' => true,
                'expected_access' => false,
            ],
            'inactive' => [
                'email' => 'inactive.user@example.com',
                'password' => 'FuzzTestPass123!',
                'is_admin' => false,
                'is_active' => false,
                'expected_access' => false,
            ],
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

        $results = [
            'test_date' => date('Y-m-d H:i:s'),
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'by_user' => [],
            'by_endpoint' => [],
            'detailed_results' => [],
        ];

        echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
        echo "║          SESSION 4 - ADMIN ENDPOINT ACCESS CONTROL TEST          ║\n";
        echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

        foreach ($testUsers as $userType => $userData) {
            $user = User::where('email', $userData['email'])->first();
            
            if (!$user) {
                echo "❌ ERROR: User not found: {$userData['email']}\n";
                continue;
            }

            echo "▶ Testing: {$userType} ({$userData['email']})\n";
            echo "  Admin: " . ($user->is_admin ? 'YES' : 'NO') . " | Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
            echo "─────────────────────────────────────────────────────────────────\n";

            $results['by_user'][$userType] = [
                'passed' => 0,
                'failed' => 0,
                'endpoints' => [],
            ];

            foreach ($adminEndpoints as $endpoint) {
                $results['total_tests']++;
                
                // Make request
                $response = $this->actingAs($user)->get($endpoint);
                
                $statusCode = $response->status();
                $isAllowed = in_array($statusCode, [200, 201]);
                $shouldBeAllowed = $user->is_admin && $user->is_active;
                
                // Determine if test passed
                $testPassed = ($isAllowed === $shouldBeAllowed);
                
                if ($testPassed) {
                    $results['passed']++;
                    $results['by_user'][$userType]['passed']++;
                    $icon = '✅';
                } else {
                    $results['failed']++;
                    $results['by_user'][$userType]['failed']++;
                    $icon = '❌';
                }

                // Expected vs actual
                $expectedStr = $shouldBeAllowed ? 'ALLOW (200)' : 'DENY (403/302)';
                $actualStr = $isAllowed ? "ALLOW ({$statusCode})" : "DENY ({$statusCode})";

                echo "  {$icon} {$endpoint}";
                echo str_repeat(' ', 28 - strlen($endpoint));
                echo " → {$actualStr}";
                echo "\n";

                $results['detailed_results'][] = [
                    'user_type' => $userType,
                    'user_email' => $user->email,
                    'endpoint' => $endpoint,
                    'is_admin' => $user->is_admin,
                    'is_active' => $user->is_active,
                    'expected_access' => $shouldBeAllowed,
                    'actual_status' => $statusCode,
                    'actual_allowed' => $isAllowed,
                    'test_passed' => $testPassed,
                ];

                if (!isset($results['by_endpoint'][$endpoint])) {
                    $results['by_endpoint'][$endpoint] = ['passed' => 0, 'failed' => 0];
                }
                
                if ($testPassed) {
                    $results['by_endpoint'][$endpoint]['passed']++;
                } else {
                    $results['by_endpoint'][$endpoint]['failed']++;
                }
            }
            
            echo "\n";
        }

        echo "╔════════════════════════════════════════════════════════════════════╗\n";
        echo "║                          TEST SUMMARY                            ║\n";
        echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

        echo "Total Tests: {$results['total_tests']}\n";
        echo "Passed: {$results['passed']} ✅\n";
        echo "Failed: {$results['failed']} ❌\n";
        echo "Success Rate: " . round(($results['passed'] / $results['total_tests']) * 100, 1) . "%\n\n";

        echo "Results by User:\n";
        foreach ($results['by_user'] as $userType => $stats) {
            $total = $stats['passed'] + $stats['failed'];
            echo "  • {$userType}: {$stats['passed']}/{$total} passed\n";
        }

        echo "\nResults by Endpoint:\n";
        foreach ($results['by_endpoint'] as $endpoint => $stats) {
            $total = $stats['passed'] + $stats['failed'];
            echo "  • {$endpoint}: {$stats['passed']}/{$total} passed\n";
        }

        // Save detailed results
        $jsonFile = 'session_4_endpoint_access_results.json';
        file_put_contents($jsonFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "\n✅ Detailed results saved to: {$jsonFile}\n";

        return $results;
    }
}

// Run the tests
$test = new AdminAccessTest();
$test->setUp();
$results = $test->runAllTests();

// Exit with appropriate code
exit($results['failed'] > 0 ? 1 : 0);
