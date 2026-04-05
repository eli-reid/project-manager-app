<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Reports\Livewire\User\FinancialReports\Index;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Timecards\Models\TimecardEntry;
use Livewire\Livewire;

it('registers the financial reports route', function (): void {
    expect(route('reports.financial.index', absolute: false))->toBe('/reports/financial');
});

it('allows users with financial reports view permission', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);

    $this->actingAs($user)
        ->get(route('reports.financial.index'))
        ->assertSuccessful()
        ->assertSee('Financial Reports');
});

it('allows admins to access financial reports route', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('reports.financial.index'))
        ->assertSuccessful()
        ->assertSee('Project Profitability');
});

it('forbids users without financial reports view permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('reports.financial.index'))
        ->assertForbidden();
});

it('renders project report metrics for the selected project', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);
    $project = Project::factory()->create();

    TimecardEntry::factory()->create([
        'project_id' => $project->id,
        'hours' => 8.5,
    ]);

    DailyReport::factory()->create([
        'project_id' => $project->id,
        'report_date' => now()->toDateString(),
    ]);

    StockOrder::factory()->forProject($project)->create();

    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 200,
        'tax_amount' => 20,
        'total_amount' => 220,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('projectId', $project->id)
        ->assertSee('Project Report')
        ->assertSee('8.50')
        ->assertSee('220.00');
});

it('exports selected project report as csv when user has export permission', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view', 'financial-reports.export']);
    $project = Project::factory()->create([
        'project_number' => 'PRJ-1001',
    ]);

    TimecardEntry::factory()->create([
        'project_id' => $project->id,
        'hours' => 10.25,
    ]);

    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 500,
        'tax_amount' => 50,
        'total_amount' => 550,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('projectId', $project->id)
        ->call('exportProjectReport')
        ->assertFileDownloaded('project-report-prj-1001.csv');
});

it('forbids project report export without export permission', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);
    $project = Project::factory()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('projectId', $project->id)
        ->call('exportProjectReport')
        ->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
function reportsUserWithPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Reports Test Role '.str()->uuid(),
        'description' => 'Role created by reports tests',
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
