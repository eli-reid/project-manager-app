<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Livewire\User\Timecards\Form as UserTimecardForm;
use App\Domains\Timecards\Models\Timecard;
use Livewire\Livewire;

it('persists cost code selections from the user timecard form', function (): void {
    $user = payrollTimecardUserWithPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);
    $project = Project::factory()->create(['is_active' => true]);
    $costCode = CostCode::factory()->create([
        'project_id' => $project->id,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(UserTimecardForm::class)
        ->set('week_starting', '2026-04-12')
        ->set('entries.0.day_of_week', 1)
        ->set('entries.0.project_id', (string) $project->id)
        ->set('entries.0.cost_code_id', (string) $costCode->id)
        ->set('entries.0.hours', '8')
        ->set('entries.0.notes', 'Cost coded work')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $timecard = Timecard::query()->where('user_id', $user->id)->whereDate('week_starting', '2026-04-12')->first();

    expect($timecard)->not->toBeNull();

    $this->assertDatabaseHas('timecard_entries', [
        'timecard_id' => $timecard?->id,
        'project_id' => $project->id,
        'cost_code_id' => $costCode->id,
    ]);
});

it('requires project selection before choosing a cost code in the user timecard form', function (): void {
    $user = payrollTimecardUserWithPermissions(['timecards.view', 'timecards.create', 'timecards.edit', 'timecards.submit']);
    $project = Project::factory()->create(['is_active' => true]);
    $costCode = CostCode::factory()->create([
        'project_id' => $project->id,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(UserTimecardForm::class)
        ->set('week_starting', '2026-04-12')
        ->set('entries.0.day_of_week', 1)
        ->set('entries.0.project_id', null)
        ->set('entries.0.cost_code_id', (string) $costCode->id)
        ->set('entries.0.hours', '8')
        ->call('save')
        ->assertHasErrors(['entries.0.cost_code_id']);
});

/**
 * @param  array<int, string>  $permissions
 */
function payrollTimecardUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Payroll Timecard Test Role '.str()->uuid(),
        'description' => 'Role for payroll phase 5B tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            $permissionId = Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');

            return is_string($permissionId) ? $permissionId : null;
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
