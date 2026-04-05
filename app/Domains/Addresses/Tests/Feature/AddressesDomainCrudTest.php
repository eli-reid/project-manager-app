<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;
use App\Domains\Addresses\Livewire\Admin\Addresses\Form;
use App\Domains\Addresses\Livewire\Admin\Addresses\InlineCreateWidget;
use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Models\Client;
use Livewire\Livewire;

it('allows authorized users to view addresses index route', function (): void {
    $user = userWithAddressDomainPermissions(['addresses.view']);

    Address::factory()->create(['address1' => '101 Main St']);

    $this->actingAs($user)
        ->get(route('admin.addresses.index'))
        ->assertSuccessful()
        ->assertSee('Addresses')
        ->assertSee('101 Main St');
});

it('creates an address through livewire form', function (): void {
    $user = userWithAddressDomainPermissions(['addresses.create', 'addresses.view', 'clients.view']);
    $client = Client::factory()->create(['company_name' => 'Address Test Client']);

    $this->actingAs($user);

    Livewire::test(Form::class)
        ->set('address1', '202 Project Ave')
        ->set('city', 'Denver')
        ->set('state', 'CO')
        ->set('zip', '80202')
        ->set('client_id', (string) $client->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Address::query()->where('address1', '202 Project Ave')->exists())->toBeTrue();
});

it('creates an address through inline widget and dispatches selection event', function (): void {
    $user = userWithAddressDomainPermissions(['addresses.create', 'clients.view']);
    $client = Client::factory()->create(['company_name' => 'Inline Address Client']);

    $this->actingAs($user);

    Livewire::test(InlineCreateWidget::class, ['client_id' => (string) $client->id])
        ->call('open')
        ->set('address1', '303 Inline Blvd')
        ->set('city', 'Austin')
        ->set('state', 'TX')
        ->call('saveInline')
        ->assertHasNoErrors()
        ->assertDispatched('address-inline-created');

    expect(Address::query()->where('address1', '303 Inline Blvd')->exists())->toBeTrue();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithAddressDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Addresses Test Role '.str()->uuid(),
        'description' => 'Role for addresses domain feature tests',
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
