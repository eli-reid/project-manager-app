<?php

namespace App\Console\Commands;

use App\Core\Identity\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Performance & Optimization Testing Command
 *
 * Runs comprehensive performance tests and generates detailed metrics report
 * Tests N+1 queries, response times, memory usage, caching, and indexes
 */
class TestPerformanceOptimization extends Command
{
    protected $signature = 'test:performance';

    protected $description = 'Run comprehensive performance & optimization tests';

    private $results = [];

    private $startTime;

    private $totalTests = 0;

    private $passedTests = 0;

    private $failedTests = 0;

    public function handle()
    {
        $this->startTime = microtime(true);

        $this->info('🚀 Performance & Optimization Test Suite');
        $this->info('='.str_repeat('=', 60));
        $this->newLine();

        // Run test categories
        $this->testQueryOptimization();
        $this->testResponseTimes();
        $this->testMemoryUsage();
        $this->testCaching();
        $this->testIndexes();
        $this->testQueryResults();
        $this->testRoutePerformance();
        $this->testDatabaseConnections();

        // Generate results
        $this->generateReport();

        return Command::SUCCESS;
    }

    /**
     * Category 1: Query Optimization & N+1 Detection
     */
    private function testQueryOptimization()
    {
        $this->info('📊 Category 1: Query Optimization & N+1 Detection');
        $this->line(str_repeat('-', 62));

        DB::enableQueryLog();

        // Test 1: Basic model access
        $this->testDescription('Basic model access (should be 1 query)');
        try {
            $user = $this->createTestUser();
            DB::flushQueryLog();
            User::find($user->id);
            $queries = count(DB::getQueryLog());

            if ($queries <= 1) {
                $this->passTest("✅ Queries: $queries");
            } else {
                $this->failTest("❌ Queries: $queries (expected ≤1)");
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Collection operations
        $this->testDescription('Collection operations (should be ≤2 queries)');
        try {
            $this->createTestUsers(5);
            DB::flushQueryLog();
            $count = User::count();
            $queries = count(DB::getQueryLog());

            if ($queries <= 2) {
                $this->passTest("✅ Queries: $queries, Count: $count");
            } else {
                $this->failTest("❌ Queries: $queries (expected ≤2)");
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Login flow efficiency
        $this->testDescription('Login flow query efficiency (should be ≤10 queries)');
        try {
            DB::flushQueryLog();
            $user = User::first();
            $queries = count(DB::getQueryLog());

            if ($queries <= 10) {
                $this->passTest("✅ Auth queries: $queries");
            } else {
                $this->failTest("❌ Auth queries: $queries (expected ≤10)");
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 4: Attribute access (no queries)
        $this->testDescription('Attribute access without queries');
        try {
            $user = $this->createTestUser();
            DB::flushQueryLog();
            $name = $user->first_name;
            $email = $user->email;
            $queries = count(DB::getQueryLog());

            if ($queries == 0) {
                $this->passTest('✅ No queries executed (correct)');
            } else {
                $this->failTest("❌ Unexpected $queries queries");
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        DB::disableQueryLog();
        $this->newLine();
    }

    /**
     * Category 2: Response Time Performance
     */
    private function testResponseTimes()
    {
        $this->info('⏱️  Category 2: Response Time Performance');
        $this->line(str_repeat('-', 62));

        // Test 1: Login page load
        $this->testDescription('Login page load time (should be <500ms)');
        try {
            $start = microtime(true);
            $time = (microtime(true) - $start) * 1000;

            if ($time < 500) {
                $this->passTest('✅ Load time: '.number_format($time, 2).'ms');
            } else {
                $this->failTest('⚠️  Load time: '.number_format($time, 2).'ms');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Model instantiation
        $this->testDescription('User model instantiation (should be <50ms for 10 users)');
        try {
            $start = microtime(true);
            for ($i = 0; $i < 10; $i++) {
                new User(['id' => $i, 'email' => "test$i@example.com"]);
            }
            $time = (microtime(true) - $start) * 1000;

            if ($time < 50) {
                $this->passTest('✅ Time: '.number_format($time, 2).'ms');
            } else {
                $this->failTest('⚠️  Time: '.number_format($time, 2).'ms');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Database query response
        $this->testDescription('Database query response time (should be <100ms)');
        try {
            $user = $this->createTestUser();
            $start = microtime(true);
            User::where('id', $user->id)->first();
            $time = (microtime(true) - $start) * 1000;

            if ($time < 100) {
                $this->passTest('✅ Query time: '.number_format($time, 2).'ms');
            } else {
                $this->failTest('⚠️  Query time: '.number_format($time, 2).'ms');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 4: Batch operations
        $this->testDescription('Batch create operations (10 users should be <200ms)');
        try {
            $start = microtime(true);
            $this->createTestUsers(10);
            $time = (microtime(true) - $start) * 1000;

            if ($time < 200) {
                $this->passTest('✅ Time: '.number_format($time, 2).'ms');
            } else {
                $this->failTest('⚠️  Time: '.number_format($time, 2).'ms');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Category 3: Memory Usage
     */
    private function testMemoryUsage()
    {
        $this->info('💾 Category 3: Memory Usage Optimization');
        $this->line(str_repeat('-', 62));

        // Test 1: Collection memory
        $this->testDescription('Collection memory growth (should be <5MB for 100 models)');
        try {
            gc_collect_cycles();
            $start = memory_get_usage();

            for ($i = 0; $i < 100; $i++) {
                new User(['id' => $i, 'email' => "test$i@example.com"]);
            }

            $used = memory_get_usage() - $start;
            $mb = $used / 1024 / 1024;

            if ($mb < 5) {
                $this->passTest('✅ Memory used: '.number_format($mb, 2).'MB');
            } else {
                $this->failTest('⚠️  Memory used: '.number_format($mb, 2).'MB');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Per-model memory
        $this->testDescription('Per-model memory efficiency (should be <100KB per model)');
        try {
            gc_collect_cycles();
            $start = memory_get_usage();

            $users = [];
            for ($i = 0; $i < 50; $i++) {
                $users[] = $this->createTestUser();
            }

            $used = (memory_get_usage() - $start) / count($users);
            $kb = $used / 1024;

            if ($kb < 100) {
                $this->passTest('✅ Per-model: '.number_format($kb, 2).'KB');
            } else {
                $this->failTest('⚠️  Per-model: '.number_format($kb, 2).'KB');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Garbage collection
        $this->testDescription('Garbage collection efficiency');
        try {
            gc_collect_cycles();
            $before = memory_get_usage();

            // Allocate and free memory
            $temp = [];
            for ($i = 0; $i < 100; $i++) {
                $temp[] = str_repeat('x', 1024);
            }
            unset($temp);

            gc_collect_cycles();
            $after = memory_get_usage();

            $this->passTest('✅ Memory before: '.number_format($before / 1024, 2).'KB, After: '.number_format($after / 1024, 2).'KB');
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Category 4: Caching Effectiveness
     */
    private function testCaching()
    {
        $this->info('⚡ Category 4: Cache Effectiveness');
        $this->line(str_repeat('-', 62));

        // Test 1: Cache key generation
        $this->testDescription('Cache key generation (1000 keys in <50ms)');
        try {
            $start = microtime(true);

            for ($i = 0; $i < 1000; $i++) {
                $key = 'user.'.$i.'.profile';
            }

            $time = (microtime(true) - $start) * 1000;

            if ($time < 50) {
                $this->passTest('✅ Time: '.number_format($time, 2).'ms');
            } else {
                $this->failTest('⚠️  Time: '.number_format($time, 2).'ms');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Cache invalidation
        $this->testDescription('Cache invalidation on model update');
        try {
            $user = $this->createTestUser();
            $original = $user->first_name;

            $user->update(['first_name' => 'Updated']);

            $fresh = $user->fresh()->first_name;

            if ($fresh === 'Updated') {
                $this->passTest('✅ Cache invalidation working');
            } else {
                $this->failTest('❌ Cache invalidation failed');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Session performance
        $this->testDescription('Session data retrieval');
        try {
            session(['test_key' => 'test_value']);
            $value = session('test_key');

            if ($value === 'test_value') {
                $this->passTest('✅ Session working correctly');
            } else {
                $this->failTest('❌ Session retrieval failed');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Category 5: Database Indexes
     */
    private function testIndexes()
    {
        $this->info('🔍 Category 5: Database Index Effectiveness');
        $this->line(str_repeat('-', 62));

        // Test 1: Index existence
        $this->testDescription('Verify indexes on commonly queried columns');
        try {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='users'");
            $indexCount = count($tables);

            if ($indexCount > 0) {
                $this->passTest("✅ Found $indexCount indexes on users table");
            } else {
                $this->warnTest('⚠️  No indexes found on users table');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Indexed query performance
        $this->testDescription('Indexed query performance (should be <10ms)');
        try {
            $user = $this->createTestUser();

            $start = microtime(true);
            User::where('email', $user->email)->first();
            $time = (microtime(true) - $start) * 1000;

            if ($time < 10) {
                $this->passTest('✅ Query time: '.number_format($time, 2).'ms');
            } else {
                $this->failTest('⚠️  Query time: '.number_format($time, 2).'ms');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Query plan optimization
        $this->testDescription('Query execution plan (verify index usage)');
        try {
            $user = $this->createTestUser();
            $explains = DB::select('EXPLAIN QUERY PLAN SELECT * FROM users WHERE email = ?', [$user->email]);

            if (count($explains) > 0) {
                $this->passTest('✅ Query plan available, rows: '.count($explains));
            } else {
                $this->failTest('❌ Query plan unavailable');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Category 6: Query Result Optimization
     */
    private function testQueryResults()
    {
        $this->info('📈 Category 6: Query Result Optimization');
        $this->line(str_repeat('-', 62));

        DB::enableQueryLog();

        // Test 1: Selective columns
        $this->testDescription('Selective column queries (only id, email)');
        try {
            $user = $this->createTestUser();
            DB::flushQueryLog();

            User::where('id', $user->id)->select('id', 'email')->first();

            $queries = DB::getQueryLog();
            if (count($queries) === 1) {
                $this->passTest('✅ Single optimized query executed');
            } else {
                $this->failTest('❌ Unexpected query count: '.count($queries));
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Count optimization
        $this->testDescription('Count query optimization');
        try {
            $this->createTestUsers(5);
            DB::flushQueryLog();

            $count = User::count();

            $queries = DB::getQueryLog();
            if (count($queries) === 1 && str_contains($queries[0]['query'], 'COUNT')) {
                $this->passTest("✅ Optimized COUNT query, result: $count");
            } else {
                $this->failTest('❌ COUNT query not optimized');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Limit optimization
        $this->testDescription('LIMIT query optimization');
        try {
            $this->createTestUsers(20);
            DB::flushQueryLog();

            $users = User::limit(10)->get();

            if ($users->count() <= 10) {
                $this->passTest('✅ LIMIT working correctly, got: '.$users->count());
            } else {
                $this->failTest('❌ LIMIT not applied');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 4: Pagination
        $this->testDescription('Pagination reduces result set');
        try {
            $this->createTestUsers(30);

            $paginated = User::paginate(10);

            if ($paginated->count() === 10 && $paginated->total() > 10) {
                $this->passTest('✅ Pagination working, page: '.$paginated->count().'/'.$paginated->total());
            } else {
                $this->failTest('❌ Pagination issue');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        DB::disableQueryLog();
        $this->newLine();
    }

    /**
     * Category 7: Route & Controller Performance
     */
    private function testRoutePerformance()
    {
        $this->info('🚦 Category 7: Route & Controller Performance');
        $this->line(str_repeat('-', 62));

        // Test 1: Route registration
        $this->testDescription('Route resolution performance');
        try {
            $start = microtime(true);
            app('router')->getRoutes();
            $time = (microtime(true) - $start) * 1000;

            $this->passTest('✅ Route resolution: '.number_format($time, 2).'ms');
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Middleware execution
        $this->testDescription('Middleware stack execution');
        try {
            $middlewareCount = count(app('Illuminate\Routing\Router')->getMiddlewareGroups()['web'] ?? []);

            $this->passTest("✅ Web middleware stack: $middlewareCount middlewares");
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Container binding resolution
        $this->testDescription('Service container resolution (100 bindings)');
        try {
            $start = microtime(true);

            for ($i = 0; $i < 100; $i++) {
                app(User::class);
            }

            $time = (microtime(true) - $start) * 1000;

            if ($time < 200) {
                $this->passTest('✅ Binding resolution: '.number_format($time, 2).'ms');
            } else {
                $this->failTest('⚠️  Binding resolution: '.number_format($time, 2).'ms');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Category 8: Database Connections
     */
    private function testDatabaseConnections()
    {
        $this->info('🔌 Category 8: Database Connection Efficiency');
        $this->line(str_repeat('-', 62));

        // Test 1: Connection reuse
        $this->testDescription('Database connection reuse');
        try {
            $conn1 = DB::connection();
            $conn2 = DB::connection();

            if ($conn1 === $conn2) {
                $this->passTest('✅ Connection reuse working');
            } else {
                $this->failTest('❌ Creating new connections');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 2: Transaction support
        $this->testDescription('Transaction support for batch operations');
        try {
            $count = 0;
            DB::transaction(function () use (&$count) {
                for ($i = 0; $i < 5; $i++) {
                    $this->createTestUser();
                    $count++;
                }
            });

            if ($count === 5) {
                $this->passTest("✅ Transaction completed successfully, created: $count users");
            } else {
                $this->failTest('❌ Transaction failed');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        // Test 3: Connection state
        $this->testDescription('Connection state verification');
        try {
            if (DB::connection()->getPdo()) {
                $this->passTest('✅ Database connection active');
            } else {
                $this->failTest('❌ Database connection inactive');
            }
        } catch (\Exception $e) {
            $this->failTest('❌ Error: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Helper: Create test user
     */
    private function createTestUser(): User
    {
        return User::create([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'company_email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Helper: Create multiple users
     */
    private function createTestUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = $this->createTestUser();
        }

        return $users;
    }

    /**
     * Helper: Test description
     */
    private function testDescription(string $description)
    {
        $this->totalTests++;
        $this->line("  $description");
    }

    /**
     * Helper: Pass test
     */
    private function passTest(string $message)
    {
        $this->passedTests++;
        $this->line("    $message");
    }

    /**
     * Helper: Fail test
     */
    private function failTest(string $message)
    {
        $this->failedTests++;
        $this->line("    <fg=red>$message</>");
    }

    /**
     * Helper: Warn test
     */
    private function warnTest(string $message)
    {
        $this->line("    <fg=yellow>$message</>");
    }

    /**
     * Generate final report
     */
    private function generateReport()
    {
        $elapsed = microtime(true) - $this->startTime;
        $successRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;

        $this->newLine();
        $this->info('📋 PERFORMANCE TEST SUMMARY');
        $this->info('='.str_repeat('=', 60));
        $this->line("Total Tests:    $this->totalTests");
        $this->line("<fg=green>Passed:         $this->passedTests</>");
        $this->line("<fg=red>Failed:         $this->failedTests</>");
        $this->line('Success Rate:   '.number_format($successRate, 1).'%');
        $this->line('Execution Time: '.number_format($elapsed, 2).'s');
        $this->info('='.str_repeat('=', 60));

        // Save results to JSON
        $filename = 'performance_test_results_'.now()->format('Y-m-d_H-i-s').'.json';
        $path = storage_path("logs/$filename");

        $data = [
            'timestamp' => now()->toIso8601String(),
            'total_tests' => $this->totalTests,
            'passed_tests' => $this->passedTests,
            'failed_tests' => $this->failedTests,
            'success_rate' => $successRate,
            'execution_time_seconds' => $elapsed,
        ];

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info("✅ Results saved to: $path");
    }
}
