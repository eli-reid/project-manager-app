<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Domains\Dailies\Livewire\Admin\Dailies\Index as AdminIndex;
use App\Domains\Dailies\Livewire\User\Dailies\Index as UserIndex;
use App\Domains\Dailies\Models\DailyReport;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from admin dailies routes', function (): void {
    get(route('admin.dailies.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without dailies view-all permission from admin index', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user);

    get(route('admin.dailies.index'))
        ->assertForbidden();
});

it('allows users with dailies.view-all to access admin dailies index', function (): void {
    $reviewer = userWithDailiesPermissions(['dailies.view-all', 'dailies.view']);
    $owner = User::factory()->create();

    DailyReport::factory()->create([
        'user_id' => $owner->id,
        'submitted_by_id' => $owner->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    actingAs($reviewer);

    Livewire::test(AdminIndex::class)
        ->assertSee('Dailies');
});

it('allows users with dailies permissions to access user dailies routes', function (): void {
    $user = userWithDailiesPermissions(['dailies.view', 'dailies.create', 'dailies.edit']);

    $report = DailyReport::factory()->create([
        'user_id' => $user->id,
        'submitted_by_id' => $user->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('dailies.index'))
        ->assertOk()
        ->assertSee('My Daily Reports');

    get(route('dailies.create'))
        ->assertOk()
        ->assertSee('Create Daily Report');

    get(route('dailies.show', $report))
        ->assertOk()
        ->assertSee('Daily Report Details');

    get(route('dailies.edit', $report))
        ->assertOk()
        ->assertSee('Edit Daily Report');
});

it('renders user dailies livewire index for authorized users', function (): void {
    $user = userWithDailiesPermissions(['dailies.view']);

    DailyReport::factory()->create([
        'user_id' => $user->id,
        'submitted_by_id' => $user->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    actingAs($user);

    Livewire::test(UserIndex::class)
        ->assertSee('My Daily Reports');
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithDailiesPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Dailies Test Role '.str()->uuid(),
        'description' => 'Role for dailies domain tests',
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
