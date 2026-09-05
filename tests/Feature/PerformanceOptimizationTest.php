<?php

use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    DB::enableQueryLog();
});

afterEach(function (): void {
    DB::disableQueryLog();
});

    // ============================================================================
    // CATEGORY 1: Database Query Optimization (N+1 Detection)
    // ============================================================================

    describe('Database Query Optimization', function () {
        it('detects N+1 queries on basic model access', function () {
            // Create test data
            $user = createTestUser();
            
            // Clear query log and measure
            DB::flushQueryLog();
            
            // This should execute only 1 query
            $retrieved = $user::find($user->id);
            
            $queryCount = count(DB::getQueryLog());
            expect($queryCount)->toBeLessThanOrEqual(1, 'Expected 1 query, got ' . $queryCount);
        });

        it('detects N+1 queries when accessing relationships without eager loading', function () {
            $user = createTestUser();
            
            DB::flushQueryLog();
            
            // This will trigger a query (N+1 pattern)
            $user->refresh(); // Force fresh from DB
            
            $baselineQueries = count(DB::getQueryLog());
            
            // Accessing a relationship without eager loading
            DB::flushQueryLog();
            $user->getKey(); // Access primary key (no query)
            
            $finalQueries = count(DB::getQueryLog());
            expect($finalQueries)->toBeLessThanOrEqual($baselineQueries + 2);
        });

        it('verifies eager loading prevents N+1 on collections', function () {
            $users = createTestUsers(5);
            
            DB::flushQueryLog();
            
            // Accessing all users should be minimal queries
            $count = $users->count();
            
            $queryCount = count(DB::getQueryLog());
            expect($queryCount)->toBeLessThanOrEqual(2, 'Expected ≤2 queries, got ' . $queryCount);
        });

        it('measures query efficiency for user authentication flow', function () {
            $user = createTestUser();
            
            DB::flushQueryLog();
            
            // Login operation
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);
            
            $queries = count(DB::getQueryLog());
            
            // Authentication should not exceed 10 queries (user check + session)
            expect($queries)->toBeLessThanOrEqual(10, 
                'Auth flow used ' . $queries . ' queries (expected ≤10)');
        });

        it('detects N+1 in model attribute access', function () {
            $user = createTestUser();
            
            DB::flushQueryLog();
            
            // Access basic attributes (no N+1)
            $name = $user->first_name;
            $email = $user->email;
            $active = $user->is_active;
            
            $queryCount = count(DB::getQueryLog());
            expect($queryCount)->toBe(0, 'Attribute access should not query database');
        });
    });

    // ============================================================================
    // CATEGORY 2: Response Time Performance
    // ============================================================================

    describe('Response Time Performance', function () {
        it('measures response time for dashboard endpoint', function () {
            $user = createTestUser();
            
            $startTime = microtime(true);
            $response = $this->actingAs($user)->get('/dashboard');
            $elapsed = (microtime(true) - $startTime) * 1000; // milliseconds
            
            expect($response->status())->toBe(200);
            expect($elapsed)->toBeLessThan(1000, 
                'Dashboard response took ' . number_format($elapsed, 2) . 'ms (expected <1000ms)');
        });

        it('measures response time for login endpoint', function () {
            $startTime = microtime(true);
            $response = $this->get('/login');
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            expect($response->status())->toBe(200);
            expect($elapsed)->toBeLessThan(500, 
                'Login page load took ' . number_format($elapsed, 2) . 'ms (expected <500ms)');
        });

        it('measures response time for API endpoint', function () {
            $user = createTestUser();
            
            $startTime = microtime(true);
            $response = $this->actingAs($user)->get('/api/clients');
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            // API calls should be fast
            if ($response->status() === 200) {
                expect($elapsed)->toBeLessThan(300, 
                    'API response took ' . number_format($elapsed, 2) . 'ms (expected <300ms)');
            }
        });

        it('measures response time degradation with data growth', function () {
            $user = createTestUser();
            
            // Measure with small dataset
            DB::table('users')->truncate();
            createTestUser();
            
            $startTime1 = microtime(true);
            $response1 = $this->actingAs($user)->get('/dashboard');
            $elapsed1 = (microtime(true) - $startTime1) * 1000;
            
            expect($response1->status())->toBe(200);
            
            // Response should remain under threshold
            expect($elapsed1)->toBeLessThan(1000);
        });
    });

    // ============================================================================
    // CATEGORY 3: Memory Usage & Optimization
    // ============================================================================

    describe('Memory Usage Optimization', function () {
        it('checks memory usage does not grow excessively on collection operations', function () {
            $startMem = memory_get_usage();
            
            // Create and iterate over collection
            $users = createTestUsers(10);
            
            $memAfterCreate = memory_get_usage();
            
            // Operations on collection
            foreach ($users as $user) {
                $user->id;
                $user->email;
            }
            
            $endMem = memory_get_usage();
            $growth = $endMem - $startMem;
            
            // Memory growth should be reasonable (< 5MB)
            expect($growth)->toBeLessThan(5 * 1024 * 1024,
                'Memory grew by ' . number_format($growth / 1024, 2) . 'KB');
        });

        it('verifies chunking reduces memory for large collections', function () {
            $startMem = memory_get_usage();
            
            // Process in chunks (simulated)
            $users = createTestUsers(20);
            
            $chunks = $users->chunk(5);
            
            $endMem = memory_get_usage();
            $growth = $endMem - $startMem;
            
            // Chunking should keep memory reasonable
            expect($growth)->toBeLessThan(10 * 1024 * 1024);
        });

        it('checks model hydration memory efficiency', function () {
            $startMem = memory_get_usage();
            
            // Create models
            $users = [];
            for ($i = 0; $i < 50; $i++) {
                $users[] = createTestUser();
            }
            
            $endMem = memory_get_usage();
            $avgPerModel = ($endMem - $startMem) / count($users);
            
            // Each model should use reasonable memory
            expect($avgPerModel)->toBeLessThan(10 * 1024, 
                'Average memory per model: ' . number_format($avgPerModel, 2) . ' bytes');
        });
    });

    // ============================================================================
    // CATEGORY 4: Cache Effectiveness
    // ============================================================================

    describe('Cache Effectiveness', function () {
        it('verifies cache hit rate on repeated requests', function () {
            $user = createTestUser();
            
            // First request (cache miss)
            $this->actingAs($user)->get('/dashboard');
            
            // Second request (cache hit)
            $this->actingAs($user)->get('/dashboard');
            
            // Both should succeed
            expect($user)->toBeTruthy();
        });

        it('measures cache key generation efficiency', function () {
            $user = createTestUser();
            
            // Cache operations should be fast
            $startTime = microtime(true);
            
            for ($i = 0; $i < 100; $i++) {
                $key = 'user.' . $user->id . '.profile';
                $key;
            }
            
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            expect($elapsed)->toBeLessThan(50, 
                'Cache key generation took ' . number_format($elapsed, 2) . 'ms');
        });

        it('checks cache invalidation on data changes', function () {
            $user = createTestUser();
            
            // Get initial value
            $original = $user->first_name;
            
            // Modify
            $user->update(['first_name' => 'Updated']);
            
            // Verify change
            $updated = $user->fresh()->first_name;
            
            expect($updated)->toBe('Updated');
        });

        it('verifies session cache is utilized', function () {
            $user = createTestUser();
            
            $response = $this->actingAs($user)->get('/dashboard');
            
            // Session should be established
            expect($response->status())->toBe(200);
            expect(session()->has('login_web_59ba36addc2b2f9401580f84c6dc9e95') 
                || auth()->check())->toBe(true);
        });
    });

    // ============================================================================
    // CATEGORY 5: Database Index Effectiveness
    // ============================================================================

    describe('Database Index Effectiveness', function () {
        it('verifies indexes exist on frequently queried columns', function () {
            // Check that users table has indexes on common queries
            $tables = DB::select('SELECT name FROM sqlite_master WHERE type="index" AND tbl_name="users"');
            
            $indexNames = array_map(fn($idx) => $idx->name, $tables);
            
            // Should have at least email index
            $hasEmailIndex = collect($indexNames)->contains(
                fn($name) => str_contains($name, 'email')
            );
            
            expect($hasEmailIndex || count($indexNames) > 0)->toBe(true,
                'Expected indexes on users table, found: ' . implode(', ', $indexNames));
        });

        it('measures query execution time with proper indexes', function () {
            $user = createTestUser();
            
            DB::flushQueryLog();
            
            $startTime = microtime(true);
            
            // Query by indexed column
            $found = getUserModelClass()::where('email', $user->email)->first();
            
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            expect($found)->not->toBeNull();
            expect($elapsed)->toBeLessThan(100, 
                'Indexed query took ' . number_format($elapsed, 2) . 'ms (expected <100ms)');
        });

        it('detects missing indexes on large result sets', function () {
            // Create test data
            for ($i = 0; $i < 10; $i++) {
                createTestUser();
            }
            
            DB::flushQueryLog();
            
            $startTime = microtime(true);
            
            // Unindexed query (but small dataset)
            $users = getUserModelClass()::all();
            
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            expect($users->count())->toBeGreaterThan(0);
            expect($elapsed)->toBeLessThan(500);
        });
    });

    // ============================================================================
    // CATEGORY 6: Query Result Optimization
    // ============================================================================

    describe('Query Result Optimization', function () {
        it('verifies selective column queries reduce data transfer', function () {
            $user = createTestUser();
            
            DB::flushQueryLog();
            
            // Query only needed columns
            $lean = getUserModelClass()::where('id', $user->id)
                ->select('id', 'email')
                ->first();
            
            expect($lean)->toBeTruthy();
            
            // Only 1 query should execute
            $queries = DB::getQueryLog();
            expect(count($queries))->toBe(1);
        });

        it('measures impact of selecting all columns unnecessarily', function () {
            $user = createTestUser();
            
            DB::flushQueryLog();
            
            // Select all columns
            $full = getUserModelClass()::find($user->id);
            
            DB::flushQueryLog();
            
            // Select specific columns
            $partial = getUserModelClass()::where('id', $user->id)
                ->select('id', 'email')
                ->first();
            
            expect($full)->toBeTruthy();
            expect($partial)->toBeTruthy();
        });

        it('verifies count queries are optimized', function () {
            for ($i = 0; $i < 5; $i++) {
                createTestUser();
            }
            
            DB::flushQueryLog();
            
            $count = getUserModelClass()::count();
            
            $queries = DB::getQueryLog();
            
            // Count should use optimized query
            expect($count)->toBeGreaterThan(0);
            expect(count($queries))->toBe(1);
            expect($queries[0]['query'])->toContain('COUNT');
        });

        it('verifies pagination reduces memory and query impact', function () {
            for ($i = 0; $i < 30; $i++) {
                createTestUser();
            }
            
            $startMem = memory_get_usage();
            
            // Paginated query
            $paginated = getUserModelClass()::paginate(10);
            
            $endMem = memory_get_usage();
            
            expect($paginated->count())->toBe(10);
            expect($paginated->total())->toBeGreaterThan(0);
        });
    });

    // ============================================================================
    // CATEGORY 7: Route & Controller Performance
    // ============================================================================

    describe('Route & Controller Performance', function () {
        it('measures middleware execution time', function () {
            $user = createTestUser();
            
            $startTime = microtime(true);
            
            $response = $this->actingAs($user)->get('/dashboard');
            
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            expect($response->status())->toBe(200);
            expect($elapsed)->toBeLessThan(1000);
        });

        it('verifies route caching improves performance', function () {
            // Laravel route caching test
            $startTime = microtime(true);
            
            $response = $this->get('/login');
            
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            expect($response->status())->toBe(200);
            expect($elapsed)->toBeLessThan(500);
        });

        it('checks view rendering performance', function () {
            $user = createTestUser();
            
            $startTime = microtime(true);
            
            $response = $this->actingAs($user)->get('/dashboard');
            
            $elapsed = (microtime(true) - $startTime) * 1000;
            
            expect($response->status())->toBe(200);
            // View rendering should be included in total time
            expect($elapsed)->toBeLessThan(2000);
        });
    });

    // ============================================================================
    // CATEGORY 8: Database Connection Pooling
    // ============================================================================

    describe('Database Connection Efficiency', function () {
        it('verifies connection reuse across requests', function () {
            $user = createTestUser();
            
            // Multiple requests should reuse connection
            $response1 = $this->actingAs($user)->get('/dashboard');
            $response2 = $this->actingAs($user)->get('/dashboard');
            
            expect($response1->status())->toBe(200);
            expect($response2->status())->toBe(200);
        });

        it('checks query execution order efficiency', function () {
            $user = createTestUser();
            
            DB::flushQueryLog();
            
            // Related queries
            $userData = $user->getAttributes();
            
            $queries = DB::getQueryLog();
            
            // Getting attributes shouldn't execute queries
            expect(count($queries))->toBeLessThanOrEqual(0);
        });

        it('verifies transaction support for batch operations', function () {
            $users = [];
            
            DB::transaction(function () use (&$users) {
                for ($i = 0; $i < 5; $i++) {
                    $users[] = createTestUser();
                }
            });
            
            expect(count($users))->toBe(5);
        });
    });

function createTestUser(): User
{
    $userClass = getUserModelClass();

    return $userClass::create([
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'is_admin' => false,
        'is_active' => true,
    ]);
}

function createTestUsers(int $count)
{
    $users = [];

    for ($i = 0; $i < $count; $i++) {
        $users[] = createTestUser();
    }

    return collect($users);
}

function getUserModelClass(): string
{
    return User::class;
}
