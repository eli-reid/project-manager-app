<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;

it('allows users with payroll reports view permission to access payroll report placeholders', function (string $routeName, string $expectedText): void {
    $user = payrollReportsUserWithPermissions(['payroll-reports.view']);

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertSuccessful()
        ->assertSee($expectedText);
})->with([
    ['reports.payroll.certified.index', 'Certified Payroll (WH-347)'],
    ['reports.payroll.tax-filings.index', 'Payroll Tax Filings (941 and W-2)'],
    ['reports.payroll.labor-cost.index', 'Payroll Labor Cost by Project and Cost Code'],
    ['reports.payroll.forecasting.index', 'Payroll Forecasting'],
    ['reports.payroll.union-remittance.index', 'Union Remittance'],
]);

/**
 * @param  array<int, string>  $permissions
 */
if (! function_exists('payrollReportsUserWithPermissions')) {
    function payrollReportsUserWithPermissions(array $permissions): User
    {
        app(DomainPermissionSynchronizer::class)->sync();

        $user = User::factory()->create(['is_admin' => false]);

        $role = Role::query()->create([
            'name' => 'Payroll Reports Test Role '.str()->uuid(),
            'description' => 'Role created by payroll reports tests',
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
