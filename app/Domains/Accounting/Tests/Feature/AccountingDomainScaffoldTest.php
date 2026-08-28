<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Accounting\Livewire\Admin\AccountingCodes\Form;
use App\Domains\Accounting\Models\AccountingCode;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from accounting scaffold routes', function (): void {
    get(route('admin.accounting-codes.index'))
        ->assertRedirect(route('login'));
});

it('forbids authenticated users without accounting permissions', function (): void {
    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    actingAs($user);

    get(route('admin.accounting-codes.index'))
        ->assertForbidden();
});

it('allows users with accounting view permission to access accounting index', function (): void {
    $user = userWithAccountingDomainPermissions(['accounting-codes.view']);

    \Livewire\Livewire::actingAs($user)
        ->test(\App\Domains\Accounting\Livewire\Admin\AccountingCodes\Index::class)
        ->assertSee('Accounting Codes');
});

it('allows users with accounting create permission to create accounting codes', function (): void {
    $user = userWithAccountingDomainPermissions([
        'accounting-codes.view',
        'accounting-codes.create',
    ]);

    actingAs($user);

    Livewire::test(Form::class)
        ->set('code', 'ACCT-BULK-100')
        ->set('name', 'Bulk Materials Group')
        ->set('description', 'Tracks shared purchases for bulk materials')
        ->call('save')
        ->assertHasNoErrors();

    expect(AccountingCode::query()->where('code', 'ACCT-BULK-100')->exists())->toBeTrue();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithAccountingDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Accounting Test Role '.str()->uuid(),
        'description' => 'Role for accounting domain tests',
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
