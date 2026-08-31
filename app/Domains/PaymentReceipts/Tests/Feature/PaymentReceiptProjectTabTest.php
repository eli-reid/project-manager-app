<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\PaymentReceipts\Livewire\Admin\Projects\PaymentReceiptTab;
use App\Domains\PaymentReceipts\Models\PaymentReceipt;
use App\Domains\Projects\Models\Project;
use Livewire\Livewire;

it('shows the pay recs tab on the project page when the user can view payment receipts', function (): void {
    $project = Project::factory()->create();
    $user = userWithPaymentReceiptPermissions(['projects.view', 'payment-receipts.view']);

    PaymentReceipt::factory()->for($project)->create([
        'amount' => 1800.00,
        'received_from' => 'Harbor Client',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project).'?tab=payment-receipts')
        ->assertSuccessful()
        ->assertSee('Pay Recs')
        ->assertSee('Harbor Client')
        ->assertSee('1,800.00');
});

it('hides the pay recs tab when the user lacks payment receipt view permission', function (): void {
    $project = Project::factory()->create();
    $user = userWithPaymentReceiptPermissions(['projects.view']);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project))
        ->assertSuccessful()
        ->assertDontSee('Pay Recs');
});

it('records a payment receipt from the project pay recs tab', function (): void {
    $project = Project::factory()->create();
    $user = userWithPaymentReceiptPermissions([
        'projects.view',
        'payment-receipts.view',
        'payment-receipts.create',
    ]);

    Livewire::actingAs($user)
        ->test(PaymentReceiptTab::class, ['project' => $project])
        ->set('receivedOn', '2026-08-21')
        ->set('amount', '2750.50')
        ->set('receivedFrom', 'Northwind Client')
        ->set('reference', 'CHK-90210')
        ->set('notes', 'Initial mobilization payment')
        ->call('recordPaymentReceipt')
        ->assertHasNoErrors();

    $paymentReceipt = PaymentReceipt::query()
        ->where('project_id', $project->id)
        ->where('reference', 'CHK-90210')
        ->first();

    expect($paymentReceipt)->not->toBeNull()
        ->and((float) $paymentReceipt?->amount)->toBe(2750.5)
        ->and($paymentReceipt?->received_from)->toBe('Northwind Client');
});

it('deletes a payment receipt from the project pay recs tab', function (): void {
    $project = Project::factory()->create();
    $user = userWithPaymentReceiptPermissions([
        'projects.view',
        'payment-receipts.view',
        'payment-receipts.delete',
    ]);

    $paymentReceipt = PaymentReceipt::factory()->for($project)->create();

    Livewire::actingAs($user)
        ->test(PaymentReceiptTab::class, ['project' => $project])
        ->call('deletePaymentReceipt', (string) $paymentReceipt->id)
        ->assertHasNoErrors();

    expect(PaymentReceipt::query()->whereKey($paymentReceipt->id)->exists())->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithPaymentReceiptPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Payment Receipt Test Role '.str()->uuid(),
        'description' => 'Role for payment receipt project tab tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
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
