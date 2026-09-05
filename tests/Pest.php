<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Expectations\Expectation;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../app/Core', '../app/Domains');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    /** @var Expectation<int> $this */
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Shared Test Helpers
|--------------------------------------------------------------------------
|
| Helpers used across multiple test files should live here to avoid duplicate
| definitions that cause "Cannot redeclare function" errors when Pest loads
| tests from multiple files.
|
*/

if (! function_exists('userWithDailiesPermissions')) {
    /**
     * Create a non-admin user and assign the given dailies permissions via a
     * freshly created role. Synchronizes domain permissions first.
     *
     * @param array<int,string> $permissions
     */
    function userWithDailiesPermissions(array $permissions)
    {
        app(\App\Core\Auth\Permission\Services\DomainPermissionSynchronizer::class)->sync();

        $user = \App\Core\Identity\Models\User::factory()->create(['is_admin' => false]);

        $role = \App\Core\Auth\Role\Models\Role::query()->create([
            'name' => 'Dailies Shared Test Role '.str()->uuid(),
            'description' => 'Role for dailies domain shared tests',
            'is_active' => true,
            'built_in' => false,
            'access_level' => 20,
        ]);

        $permissionIds = collect($permissions)
            ->map(function (string $permission): ?string {
                [$resource, $action] = explode('.', $permission, 2);

                return \App\Core\Auth\Permission\Models\Permission::query()
                    ->where('resource', $resource)
                    ->where('action', $action)
                    ->value('id');
            })
            ->filter()
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->sync([$role->id]);

        return $user->fresh();
    }
}
