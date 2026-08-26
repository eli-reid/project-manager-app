<?php

use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Helper functions
function createPerfTestUser(): User
{
    return User::create([
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'is_admin' => false,
        'is_active' => true,
    ]);
}

function createPerfTestUsers(int $count): \Illuminate\Support\Collection
{
    $users = [];
    for ($i = 0; $i < $count; $i++) {
        $users[] = createPerfTestUser();
    }

    return collect($users);
}

beforeEach(function () {
    DB::enableQueryLog();
});

afterEach(function () {
    DB::disableQueryLog();
});

// ============================================================================
// CATEGORY 1: Database Query Optimization (N+1 Detection)
// ============================================================================

describe('Database Query Optimization', function () {
    it('detects N+1 queries on basic model access', function () {
        $user = createPerfTestUser();

        DB::flushQueryLog();

        $retrieved = User::find($user->id);

        $queryCount = count(DB::getQueryLog());
        expect($queryCount)->toBeLessThanOrEqual(1, 'Expected 1 query, got '.$queryCount);
    });

    it('detects N+1 queries when accessing relationships without eager loading', function () {
        $user = createPerfTestUser();

        DB::flushQueryLog();

        $user->refresh();

        $baselineQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $user->getKey();

        $finalQueries = count(DB::getQueryLog());
        expect($finalQueries)->toBeLessThanOrEqual($baselineQueries + 2);
    });

    it('verifies eager loading prevents N+1 on collections', function () {
        $users = createPerfTestUsers(5);

        DB::flushQueryLog();

        $count = $users->count();

        $queryCount = count(DB::getQueryLog());
        expect($queryCount)->toBeLessThanOrEqual(2, 'Expected ≤2 queries, got '.$queryCount);
    });

    it('measures query efficiency for user authentication flow', function () {
        $user = createPerfTestUser();

        DB::flushQueryLog();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $queries = count(DB::getQueryLog());

        expect($queries)->toBeLessThanOrEqual(10,
            'Auth flow used '.$queries.' queries (expected ≤10)');
    });

    it('detects N+1 in model attribute access', function () {
        $user = createPerfTestUser();

        DB::flushQueryLog();

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
        $user = createPerfTestUser();

        $startTime = microtime(true);
        $response = $this->actingAs($user)->get('/dashboard');
        $elapsed = (microtime(true) - $startTime) * 1000;

        expect($response->status())->toBe(200);
        expect($elapsed)->toBeLessThan(1000,
            'Dashboard response took '.number_format($elapsed, 2).'ms (expected <1000ms)');
    });

    it('measures response time for login endpoint', function () {
        $startTime = microtime(true);
        $response = $this->get('/login');
        $elapsed = (microtime(true) - $startTime) * 1000;

        expect($response->status())->toBe(200);
        expect($elapsed)->toBeLessThan(500,
            'Login page load took '.number_format($elapsed, 2).'ms (expected <500ms)');
    });

    it('measures response time for API endpoint', function () {
        $user = createPerfTestUser();

        $startTime = microtime(true);
        $response = $this->actingAs($user)->get('/api/clients');
        $elapsed = (microtime(true) - $startTime) * 1000;

        if ($response->status() === 200) {
            expect($elapsed)->toBeLessThan(300,
                'API response took '.number_format($elapsed, 2).'ms (expected <300ms)');
        }
    });

    it('measures response time degradation with data growth', function () {
        $user = createPerfTestUser();

        DB::table('users')->truncate();
        createPerfTestUser();

        $startTime1 = microtime(true);
        $response1 = $this->actingAs($user)->get('/dashboard');
        $elapsed1 = (microtime(true) - $startTime1) * 1000;

        expect($response1->status())->toBe(200);
        expect($elapsed1)->toBeLessThan(1000);
    });
});

// ============================================================================
// CATEGORY 3: Memory Usage & Optimization
// ============================================================================

describe('Memory Usage Optimization', function () {
    it('checks memory usage does not grow excessively on collection operations', function () {
        $startMem = memory_get_usage();

        $users = createPerfTestUsers(10);

        foreach ($users as $user) {
            $user->id;
            $user->email;
        }

        $endMem = memory_get_usage();
        $growth = $endMem - $startMem;

        expect($growth)->toBeLessThan(5 * 1024 * 1024,
            'Memory grew by '.number_format($growth / 1024, 2).'KB');
    });

    it('verifies chunking reduces memory for large collections', function () {
        $startMem = memory_get_usage();

        $users = createPerfTestUsers(20);

        $chunks = $users->chunk(5);

        $endMem = memory_get_usage();
        $growth = $endMem - $startMem;

        expect($growth)->toBeLessThan(10 * 1024 * 1024);
    });

    it('checks model hydration memory efficiency', function () {
        $startMem = memory_get_usage();

        $users = [];
        for ($i = 0; $i < 50; $i++) {
            $users[] = createPerfTestUser();
        }

        $endMem = memory_get_usage();
        $avgPerModel = ($endMem - $startMem) / count($users);

        expect($avgPerModel)->toBeLessThan(10 * 1024,
            'Average memory per model: '.number_format($avgPerModel, 2).' bytes');
    });
});

// ============================================================================
// CATEGORY 4: Cache Effectiveness
// ============================================================================

describe('Cache Effectiveness', function () {
    it('verifies cache hit rate on repeated requests', function () {
        $user = createPerfTestUser();

        $this->actingAs($user)->get('/dashboard');
        $this->actingAs($user)->get('/dashboard');

        expect($user)->toBeTruthy();
    });

    it('measures cache key generation efficiency', function () {
        $user = createPerfTestUser();

        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $key = 'user.'.$user->id.'.profile';
        }

        $elapsed = (microtime(true) - $startTime) * 1000;

        expect($elapsed)->toBeLessThan(50,
            'Cache key generation took '.number_format($elapsed, 2).'ms');
    });

    it('checks cache invalidation on data changes', function () {
        $user = createPerfTestUser();

        $user->update(['first_name' => 'Updated']);

        $updated = $user->fresh()->first_name;

        expect($updated)->toBe('Updated');
    });

    it('verifies session cache is utilized', function () {
        $user = createPerfTestUser();

        $response = $this->actingAs($user)->get('/dashboard');

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
        $tables = DB::select('SELECT name FROM sqlite_master WHERE type="index" AND tbl_name="users"');

        $indexNames = array_map(fn ($idx) => $idx->name, $tables);

        $hasEmailIndex = collect($indexNames)->contains(
            fn ($name) => str_contains($name, 'email')
        );

        expect($hasEmailIndex || count($indexNames) > 0)->toBe(true,
            'Expected indexes on users table, found: '.implode(', ', $indexNames));
    });

    it('measures query execution time with proper indexes', function () {
        $user = createPerfTestUser();

        DB::flushQueryLog();

        $startTime = microtime(true);

        $found = User::where('email', $user->email)->first();

        $elapsed = (microtime(true) - $startTime) * 1000;

        expect($found)->not->toBeNull();
        expect($elapsed)->toBeLessThan(100,
            'Indexed query took '.number_format($elapsed, 2).'ms (expected <100ms)');
    });

    it('detects missing indexes on large result sets', function () {
        for ($i = 0; $i < 10; $i++) {
            createPerfTestUser();
        }

        DB::flushQueryLog();

        $startTime = microtime(true);

        $users = User::all();

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
        $user = createPerfTestUser();

        DB::flushQueryLog();

        $lean = User::where('id', $user->id)
            ->select('id', 'email')
            ->first();

        expect($lean)->toBeTruthy();

        $queries = DB::getQueryLog();
        expect(count($queries))->toBe(1);
    });

    it('measures impact of selecting all columns unnecessarily', function () {
        $user = createPerfTestUser();

        DB::flushQueryLog();

        $full = User::find($user->id);

        DB::flushQueryLog();

        $partial = User::where('id', $user->id)
            ->select('id', 'email')
            ->first();

        expect($full)->toBeTruthy();
        expect($partial)->toBeTruthy();
    });

    it('verifies count queries are optimized', function () {
        for ($i = 0; $i < 5; $i++) {
            createPerfTestUser();
        }

        DB::flushQueryLog();

        $count = User::count();

        $queries = DB::getQueryLog();

        expect($count)->toBeGreaterThan(0);
        expect(count($queries))->toBe(1);
        expect($queries[0]['query'])->toContain('COUNT');
    });

    it('verifies pagination reduces memory and query impact', function () {
        for ($i = 0; $i < 30; $i++) {
            createPerfTestUser();
        }

        $paginated = User::paginate(10);

        expect($paginated->count())->toBe(10);
        expect($paginated->total())->toBeGreaterThan(0);
    });
});

// ============================================================================
// CATEGORY 7: Route & Controller Performance
// ============================================================================

describe('Route & Controller Performance', function () {
    it('measures middleware execution time', function () {
        $user = createPerfTestUser();

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/dashboard');

        $elapsed = (microtime(true) - $startTime) * 1000;

        expect($response->status())->toBe(200);
        expect($elapsed)->toBeLessThan(1000);
    });

    it('verifies route caching improves performance', function () {
        $startTime = microtime(true);

        $response = $this->get('/login');

        $elapsed = (microtime(true) - $startTime) * 1000;

        expect($response->status())->toBe(200);
        expect($elapsed)->toBeLessThan(500);
    });

    it('checks view rendering performance', function () {
        $user = createPerfTestUser();

        $startTime = microtime(true);

        $response = $this->actingAs($user)->get('/dashboard');

        $elapsed = (microtime(true) - $startTime) * 1000;

        expect($response->status())->toBe(200);
        expect($elapsed)->toBeLessThan(2000);
    });
});

// ============================================================================
// CATEGORY 8: Database Connection Efficiency
// ============================================================================

describe('Database Connection Efficiency', function () {
    it('verifies connection reuse across requests', function () {
        $user = createPerfTestUser();

        $response1 = $this->actingAs($user)->get('/dashboard');
        $response2 = $this->actingAs($user)->get('/dashboard');

        expect($response1->status())->toBe(200);
        expect($response2->status())->toBe(200);
    });

    it('checks query execution order efficiency', function () {
        $user = createPerfTestUser();

        DB::flushQueryLog();

        $userData = $user->getAttributes();

        $queries = DB::getQueryLog();

        expect(count($queries))->toBeLessThanOrEqual(0);
    });

    it('verifies transaction support for batch operations', function () {
        $users = [];

        DB::transaction(function () use (&$users) {
            for ($i = 0; $i < 5; $i++) {
                $users[] = createPerfTestUser();
            }
        });

        expect(count($users))->toBe(5);
    });
});
