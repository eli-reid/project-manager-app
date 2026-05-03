<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Projects\Models\Project;
use App\Domains\Reports\Livewire\User\LeaveBalanceSummary\Index;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Livewire\Livewire;

it('registers the leave balance summary route', function (): void {
    expect(route('reports.operational.leave-balance-summary.index', absolute: false))
        ->toBe('/reports/operational/leave-balance-summary');
});

it('allows users with operational reports view permission to access the report', function (): void {
    $user = leaveReportUserWithPermissions(['operational-reports.view']);

    $this->actingAs($user)
        ->get(route('reports.operational.leave-balance-summary.index'))
        ->assertSuccessful();
});

it('forbids users without operational reports view permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('reports.operational.leave-balance-summary.index'))
        ->assertForbidden();
});

it('renders employee leave balances', function (): void {
    $user = leaveReportUserWithPermissions(['operational-reports.view']);

    $employee = User::factory()->create([
        'is_active' => true,
        'is_built_in' => false,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    PayrollEmployeeProfile::factory()->create([
        'user_id' => $employee->id,
        'sick_hours_allowance' => 40.0,
        'vacation_hours_allowance' => 80.0,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Jane Doe')
        ->assertSee('40.00')
        ->assertSee('80.00');
});

it('excludes built-in and inactive users from the report', function (): void {
    $user = leaveReportUserWithPermissions(['operational-reports.view']);

    $builtIn = User::factory()->create(['is_built_in' => true, 'is_active' => true]);
    $inactive = User::factory()->create(['is_built_in' => false, 'is_active' => false]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertDontSee($builtIn->name)
        ->assertDontSee($inactive->name);
});

it('shows sick used hours based on approved timecards', function (): void {
    $viewer = leaveReportUserWithPermissions(['operational-reports.view']);

    $employee = User::factory()->create(['is_active' => true, 'is_built_in' => false]);

    PayrollEmployeeProfile::factory()->create([
        'user_id' => $employee->id,
        'sick_hours_allowance' => 40.0,
        'vacation_hours_allowance' => 80.0,
        'hire_date' => now()->subYears(2)->toDateString(),
    ]);

    $sickProject = Project::factory()->create(['leave_category' => 'sick']);

    $timecard = Timecard::factory()->create([
        'user_id' => $employee->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $employee->id,
        'project_id' => $sickProject->id,
        'hours' => 8.0,
        'date' => now()->toDateString(),
    ]);

    $component = Livewire::actingAs($viewer)->test(Index::class);

    $rows = collect($component->viewData('rows'));
    $row = $rows->firstWhere('name', $employee->name);

    expect($row)->not->toBeNull()
        ->and($row['sick_used'])->toBe(8.0)
        ->and($row['sick_remaining'])->toBe(32.0);
});

it('exports a csv file with leave balances', function (): void {
    $user = leaveReportUserWithPermissions(['operational-reports.view']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('exportCsv')
        ->assertFileDownloaded();
});

if (! function_exists('leaveReportUserWithPermissions')) {
    function leaveReportUserWithPermissions(array $permissions): User
    {
        app(DomainPermissionSynchronizer::class)->sync();

        $user = User::factory()->create(['is_admin' => false]);

        $role = Role::query()->create([
            'name' => 'Leave Report Test Role '.str()->uuid(),
            'description' => 'Role created by leave balance report tests',
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
        $user->roles()->attach($role->id);

        return $user;
    }
}
