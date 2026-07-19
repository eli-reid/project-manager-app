<?php

declare(strict_types=1);

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\UI\Navigation\DTO\NavItem;
use App\Core\UI\Navigation\DTO\NavSectionEnum;
use App\Core\UI\Navigation\Services\NavigationManager;
use Illuminate\Support\Facades\Gate;

it('resolves domain-owned admin and user sidebar routes from provider registrations', function (): void {
    $user = navigationUserWithAllPermissions();

    $this->actingAs($user);

    $sections = collect(app(NavigationManager::class)->resolve())->keyBy('key');

    $userRoutes = collect($sections['user']['items'] ?? [])->pluck('route')->all();
    $adminRoutes = collect($sections['admin']['items'] ?? [])->pluck('route')->all();

    expect($userRoutes)->toContain(
        'projects.index',
        'timecards.index',
        'dailies.index',
        'stock-orders.index',
        'documents.index',
    );

    expect($adminRoutes)->toContain(
        'admin.projects.index',
        'admin.accounting-codes.index',
        'admin.clients.index',
        'admin.addresses.index',
        'admin.timecards.index',
        'admin.timecards.required-users',
        'admin.dailies.index',
        'admin.stock-orders.index',
        'admin.stock-order-templates.index',
        'admin.invoices.index',
        'admin.documents.index',
        'admin.payroll.timecards.review',
        'admin.payroll.reports.weekly-employee-hours',
        'admin.payroll.reports.weekly-hour-adjustments',
        'admin.payroll.rates.index',
        'admin.payroll.rate-types.index',
        'admin.payroll.runs.index',
        'admin.reports.index',
    );
});

it('supports permission descriptors with gate arguments when resolving visibility', function (): void {
    Gate::define('navigation-test.view', fn (User $user, string $resource): bool => $resource === 'alpha');

    $user = User::factory()->create();

    $this->actingAs($user);

    $manager = new NavigationManager;
    $manager->registerSection('test', 'Test', 0);
    $manager->registerItem('test', null, new NavItem(
        id: 'alpha',
        label: 'Alpha',
        icon: null,
        url: null,
        route: 'dashboard',
        group: null,
        order: 0,
        active: false,
        visible: true,
        permissions: [[
            'ability' => 'navigation-test.view',
            'arguments' => ['alpha'],
        ]],
        section: NavSectionEnum::USER,
        meta: [],
    ));

    expect(collect($manager->resolve())->first()['items'])->toHaveCount(1);
});

function navigationUserWithAllPermissions(): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => true]);

    $role = Role::query()->create([
        'name' => 'Navigation Test Role '.str()->uuid(),
        'description' => 'Role for navigation registration tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 100,
    ]);

    $role->permissions()->sync(Permission::query()->pluck('id')->all());
    $user->roles()->sync([$role->id]);
    $user->flushAuthorizationCache();

    return $user->fresh();
}
