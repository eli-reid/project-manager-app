<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Livewire\Admin\Clients\Form;
use App\Domains\Clients\Livewire\Admin\Clients\InlineCreateWidget;
use App\Domains\Clients\Models\Client;
use Livewire\Livewire;

it('allows authorized users to view clients index route', function (): void {
    $user = userWithClientDomainPermissions(['clients.view']);

    Client::factory()->create(['company_name' => 'Vertex Builders']);

    $this->actingAs($user)
        ->get(route('admin.clients.index'))
        ->assertSuccessful()
        ->assertSee('Clients')
        ->assertSee('Vertex Builders');
});

it('creates a client through livewire form', function (): void {
    $user = userWithClientDomainPermissions(['clients.create', 'clients.view']);

    $this->actingAs($user);

    Livewire::test(Form::class)
        ->set('company_name', 'North Ridge Construction')
        ->set('contact_name', 'Eli Reid')
        ->set('email', 'eli@example.test')
        ->set('phone', '555-0100')
        ->set('addresses.0.address1', '100 Client Row')
        ->set('addresses.0.city', 'Denver')
        ->set('addresses.0.state', 'CO')
        ->set('addresses.0.zip', '80202')
        ->call('save')
        ->assertHasNoErrors();

    $client = Client::query()->where('company_name', 'North Ridge Construction')->first();

    expect($client)->not->toBeNull()
        ->and(Address::query()->where('client_id', $client?->id)->where('address1', '100 Client Row')->exists())->toBeTrue();
});

it('creates a client through inline widget and dispatches selection event', function (): void {
    $user = userWithClientDomainPermissions(['clients.create']);

    $this->actingAs($user);

    Livewire::test(InlineCreateWidget::class)
        ->call('open')
        ->set('company_name', 'Inline Client LLC')
        ->set('contact_name', 'Inline Contact')
        ->call('saveInline')
        ->assertHasNoErrors()
        ->assertDispatched('client-inline-created');

    expect(Client::query()->where('company_name', 'Inline Client LLC')->exists())->toBeTrue();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithClientDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Clients Test Role '.str()->uuid(),
        'description' => 'Role for clients domain feature tests',
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
