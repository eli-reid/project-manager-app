<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Livewire\Admin\Projects\TimecardTab;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('allows admin quick add to target a selected user with a single large project entry', function (): void {
    $admin = projectTabTimecardUserWithPermissions(['timecards.create']);
    $targetUser = User::factory()->create(['is_active' => true]);
    $project = Project::factory()->create();

    actingAs($admin);

    Livewire::test(TimecardTab::class, ['project' => $project])
        ->set('quickAddUserId', (string) $targetUser->id)
        ->set('quickAddDate', '2026-06-17')
        ->set('quickAddHours', '100')
        ->set('quickAddNotes', 'Backfill migration hours')
        ->call('addProjectHours')
        ->assertHasNoErrors();

    $timecard = Timecard::query()
        ->where('user_id', $targetUser->id)
        ->whereDate('week_starting', '2026-06-15')
        ->first();

    expect($timecard)->not->toBeNull()
        ->and($timecard?->status)->toBe(Timecard::STATUS_DRAFT)
        ->and((float) $timecard?->total_hours)->toBe(100.0);

    $entry = TimecardEntry::query()
        ->where('timecard_id', $timecard?->id)
        ->where('project_id', $project->id)
        ->first();

    expect($entry)->not->toBeNull()
        ->and((float) $entry?->hours)->toBe(100.0)
        ->and($entry?->user_id)->toBe($targetUser->id)
        ->and($entry?->notes)->toBe('Backfill migration hours');
});

it('reuses an existing draft week timecard when quick adding project hours', function (): void {
    $admin = projectTabTimecardUserWithPermissions(['timecards.create']);
    $targetUser = User::factory()->create(['is_active' => true]);
    $project = Project::factory()->create();

    $existing = Timecard::factory()->create([
        'user_id' => $targetUser->id,
        'status' => Timecard::STATUS_DRAFT,
        'week_starting' => '2026-06-15',
        'week_ending' => '2026-06-21',
        'total_hours' => 0,
    ]);

    actingAs($admin);

    Livewire::test(TimecardTab::class, ['project' => $project])
        ->set('quickAddUserId', (string) $targetUser->id)
        ->set('quickAddDate', '2026-06-18')
        ->set('quickAddHours', '12.5')
        ->call('addProjectHours')
        ->assertHasNoErrors();

    expect(Timecard::query()->where('user_id', $targetUser->id)->whereDate('week_starting', '2026-06-15')->count())
        ->toBe(1)
        ->and((float) $existing->fresh()->total_hours)
        ->toBe(12.5);
});

/**
 * @param  array<int, string>  $permissions
 */
function projectTabTimecardUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Project Tab Timecard Test '.str()->uuid(),
        'description' => 'Role for project tab quick add timecard tests',
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
