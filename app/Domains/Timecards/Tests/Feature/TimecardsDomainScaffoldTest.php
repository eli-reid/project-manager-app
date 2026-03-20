<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Domains\Timecards\Livewire\Admin\Timecards\Index;
use App\Domains\Timecards\Models\Timecard;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from timecards admin routes', function (): void {
    get(route('admin.timecards.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without timecard view permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user);

    get(route('admin.timecards.index'))
        ->assertForbidden();
});

it('allows users with timecard view permissions to access index', function (): void {
    $viewer = userWithTimecardDomainPermissions(['timecards.view']);
    $owner = User::factory()->create();

    Timecard::factory()->create([
        'user_id' => $owner->id,
        'status' => Timecard::STATUS_DRAFT,
        'total_hours' => 32.5,
    ]);

    actingAs($viewer);

    Livewire::test(Index::class)
        ->assertSee('Timecards');
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithTimecardDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Timecards Test Role '.str()->uuid(),
        'description' => 'Role for timecards domain tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
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
