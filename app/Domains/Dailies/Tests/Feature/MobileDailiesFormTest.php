<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Dailies\Livewire\User\Dailies\Form as UserForm;
use App\Domains\Dailies\Livewire\User\Dailies\Index as UserIndex;
use App\Domains\Dailies\Models\DailyReport;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the mobile dailies index', function (): void {
    $user = userWithDailiesPermissions(['dailies.view']);

    DailyReport::factory()->create([
        'user_id' => $user->id,
        'submitted_by_id' => $user->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('dailies.mobile.index'))
        ->assertOk()
        ->assertSeeLivewire(UserIndex::class)
        ->assertSee('Daily Reports')
        ->assertSee('data-pwa-mobile-nav', false);
});

it('renders the mobile dailies create form', function (): void {
    $user = userWithDailiesPermissions(['dailies.create', 'dailies.submit']);

    actingAs($user);

    get(route('dailies.mobile.create'))
        ->assertOk()
        ->assertSeeLivewire(UserForm::class)
        ->assertSee('Work Performed')
        ->assertSee('Save & Submit')
        ->assertSee('data-pwa-mobile-nav', false);
});

it('renders the mobile dailies edit form', function (): void {
    $user = userWithDailiesPermissions(['dailies.view', 'dailies.edit']);

    $report = DailyReport::factory()->create([
        'user_id' => $user->id,
        'submitted_by_id' => $user->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('dailies.mobile.edit', $report))
        ->assertOk()
        ->assertSeeLivewire(UserForm::class)
        ->assertSee('Work Performed');
});

it('renders the mobile dailies show page', function (): void {
    $user = userWithDailiesPermissions(['dailies.view']);

    $report = DailyReport::factory()->create([
        'user_id' => $user->id,
        'submitted_by_id' => $user->id,
        'status' => DailyReport::STATUS_DRAFT,
    ]);

    actingAs($user);

    get(route('dailies.mobile.show', $report))
        ->assertOk()
        ->assertSee('Daily Report')
        ->assertSee('Back')
        ->assertSee('data-pwa-mobile-nav', false);
});

it('redirects guests from mobile dailies routes', function (): void {
    get(route('dailies.mobile.index'))
        ->assertRedirect(route('login'));

    get(route('dailies.mobile.create'))
        ->assertRedirect(route('login'));
});

it('forbids unauthorized users from mobile dailies create route', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user);

    get(route('dailies.mobile.create'))
        ->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithDailiesPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Mobile Dailies Test Role '.str()->uuid(),
        'description' => 'Role for mobile dailies feature tests',
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
