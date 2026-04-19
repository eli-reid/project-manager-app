<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Reports\Livewire\User\LaborCostAnalysis\Index as LaborCostIndex;
use App\Domains\Reports\Livewire\User\MaterialCostAnalysis\Index as MaterialCostIndex;
use App\Domains\Reports\Livewire\User\MonthlyPerformance\Index as MonthlyPerfIndex;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Timecards\Models\TimecardEntry;
use Livewire\Livewire;

// ─────────────────────────────────────────
// Snapshot Stability: Report Data Consistency
// ─────────────────────────────────────────

it('monthly performance report renders for admin with year filter', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user);

    Livewire::test(MonthlyPerfIndex::class)
        ->set('year', (string) now()->year)
        ->assertSee('January')
        ->assertSee('December')
        ->assertSee('Total');
});

it('monthly performance report rows sum to totals shown in footer', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $project = Project::factory()->create();

    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoice_date' => now()->startOfYear()->toDateString(),
        'subtotal' => 1000,
        'tax_amount' => 100,
        'total_amount' => 1100,
    ]);

    $this->actingAs($user);

    Livewire::test(MonthlyPerfIndex::class)
        ->set('year', (string) now()->year)
        ->assertSee('1,100.00');
});

it('monthly performance report renders when stock orders exist without stored totals', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $project = Project::factory()->create();

    StockOrder::factory()->forProject($project)->create([
        'created_at' => now()->startOfYear()->addDays(5),
    ]);

    $this->actingAs($user);

    Livewire::test(MonthlyPerfIndex::class)
        ->set('year', (string) now()->year)
        ->assertSee('January')
        ->assertSee('Total');
});

it('labor cost analysis renders with project grouping', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);
    $project = Project::factory()->create();

    TimecardEntry::factory()->create([
        'project_id' => $project->id,
        'hours' => 12.5,
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user);

    Livewire::test(LaborCostIndex::class)
        ->set('drillDown', 'project')
        ->assertSee('12.50');
});

it('labor cost analysis can drill down by employee', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);
    $employee = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);
    $project = Project::factory()->create();

    TimecardEntry::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'hours' => 8.0,
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user);

    Livewire::test(LaborCostIndex::class)
        ->set('drillDown', 'employee')
        ->assertSee('Jane Doe')
        ->assertSee('8.00');
});

it('material cost analysis renders with project grouping and shows invoice totals', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);
    $project = Project::factory()->create();

    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 750,
        'tax_amount' => 75,
        'total_amount' => 825,
    ]);

    $this->actingAs($user);

    Livewire::test(MaterialCostIndex::class)
        ->set('drillDown', 'project')
        ->assertSee('825.00');
});

it('material cost analysis can drill down by cost type', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']);
    $project = Project::factory()->create();

    Invoice::factory()->create([
        'project_id' => $project->id,
        'invoice_date' => now()->toDateString(),
        'subtotal' => 300,
        'tax_amount' => 30,
        'total_amount' => 330,
    ]);

    StockOrder::factory()->forProject($project)->create([
        'notes' => 'Includes material spend',
    ]);

    $this->actingAs($user);

    Livewire::test(MaterialCostIndex::class)
        ->set('drillDown', 'type')
        ->assertSee('Vendor Invoices')
        ->assertSee('Stock Orders');
});

it('labor cost analysis csv export is gated behind export permission', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']); // no export permission

    $this->actingAs($user);

    Livewire::test(LaborCostIndex::class)
        ->call('exportCsv')
        ->assertForbidden();
});

it('material cost analysis csv export is gated behind export permission', function (): void {
    $user = reportsUserWithPermissions(['financial-reports.view']); // no export permission

    $this->actingAs($user);

    Livewire::test(MaterialCostIndex::class)
        ->call('exportCsv')
        ->assertForbidden();
});

/**
 * @param  array<int, string>  $permissions
 */
if (! function_exists('reportsUserWithPermissions')) {
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
}
