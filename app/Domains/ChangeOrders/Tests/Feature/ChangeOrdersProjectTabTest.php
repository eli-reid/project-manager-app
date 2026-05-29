<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Livewire\Admin\Projects\ProjectTab;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DomainPermissionSynchronizer::class)->sync();
    $this->admin = changeOrdersProjectTabUser(['change-orders.view-any']);
    $this->project = Project::factory()->create();
});

it('renders the change orders project tab with empty state', function (): void {
    actingAs($this->admin);

    Livewire::test(ProjectTab::class, [
        'project' => $this->project,
        'changeOrders' => collect(),
        'changeOrderCount' => 0,
    ])
        ->assertSee('No change orders yet.')
        ->assertSee('Change Orders');
});

it('displays change orders in the project tab', function (): void {
    actingAs($this->admin);

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $this->project->id,
        'title' => 'Foundation Revision',
        'status' => ChangeOrder::STATUS_SUBMITTED,
        'total_amount' => '5000.00',
    ]);

    Livewire::test(ProjectTab::class, [
        'project' => $this->project,
        'changeOrders' => collect([$changeOrder]),
        'changeOrderCount' => 1,
    ])
        ->assertSee('Foundation Revision')
        ->assertSee('Submitted')
        ->assertSee('5,000.00');
});

/**
 * @param  array<int, string>  $permissions
 */
function changeOrdersProjectTabUser(array $permissions): User
{
    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'CO Project Tab Role '.str()->uuid(),
        'description' => 'Role for CO project tab tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
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
