<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Livewire\Admin\Invoices\Form;
use App\Domains\Invoices\Livewire\Admin\Invoices\Show;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Models\InvoiceLineItem;
use App\Domains\Projects\Models\Project;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Authorization
// ---------------------------------------------------------------------------

it('redirects guests from the invoices index', function (): void {
    $this->get(route('admin.invoices.index'))
        ->assertRedirect(route('login'));
});

it('forbids users without invoice permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.invoices.index'))
        ->assertForbidden();
});

it('allows users with invoice view permission to access the index', function (): void {
    $user = userWithInvoicePermissions(['invoices.view']);
    Invoice::factory()->for(Project::factory())->create([
        'vendor_name' => 'Test Vendor Inc',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.invoices.index'))
        ->assertSuccessful()
        ->assertSee('Invoices')
        ->assertSee('Test Vendor Inc');
});

// ---------------------------------------------------------------------------
// Index filtering
// ---------------------------------------------------------------------------

it('filters invoices by status', function (): void {
    $user = userWithInvoicePermissions(['invoices.view']);
    $project = Project::factory()->create();

    Invoice::factory()->for($project)->create([
        'vendor_name' => 'Pending Vendor',
        'status' => InvoiceStatusEnum::Pending->value,
        'created_by' => $user->id,
    ]);
    Invoice::factory()->for($project)->create([
        'vendor_name' => 'Paid Vendor',
        'status' => InvoiceStatusEnum::Paid->value,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.invoices.index', ['status' => 'pending']))
        ->assertSee('Pending Vendor')
        ->assertDontSee('Paid Vendor');
});

// ---------------------------------------------------------------------------
// Create / Store
// ---------------------------------------------------------------------------

it('shows the create form to authorised users', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);

    $this->actingAs($user)
        ->get(route('admin.invoices.create'))
        ->assertSuccessful()
        ->assertSee('Create Invoice');
});

it('creates an invoice with line items via livewire form', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('project_id', $project->id)
        ->set('vendor_name', 'Acme Supply Co.')
        ->set('invoice_number', 'INV-0001')
        ->set('invoice_date', '2026-03-24')
        ->set('status', 'pending')
        ->set('lineItems', [
            ['description' => 'Lumber', 'quantity' => '10', 'unit_price' => '25.00', 'total' => '250.00', 'sort_order' => 0],
        ])
        ->set('subtotal', '250.00')
        ->set('tax_amount', '25.00')
        ->set('total_amount', '275.00')
        ->call('save');

    $invoice = Invoice::query()->where('vendor_name', 'Acme Supply Co.')->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->invoice_number)->toBe('INV-0001');
    expect((float) $invoice->total_amount)->toBe(275.00);

    $lineItem = InvoiceLineItem::query()->where('invoice_id', $invoice->id)->first();
    expect($lineItem)->not->toBeNull();
    expect($lineItem->description)->toBe('Lumber');
});

it('creates an invoice using totals only without line items', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('project_id', $project->id)
        ->set('vendor_name', 'Totals Only Vendor')
        ->set('invoice_number', 'INV-TOTALS-01')
        ->set('invoice_date', '2026-03-24')
        ->set('status', 'pending')
        ->set('lineItems', [])
        ->set('subtotal', '250.00')
        ->set('tax_amount', '25.00')
        ->call('save');

    $invoice = Invoice::query()->where('invoice_number', 'INV-TOTALS-01')->first();
    expect($invoice)->not->toBeNull();
    expect((float) $invoice->subtotal)->toBe(250.00);
    expect((float) $invoice->tax_amount)->toBe(25.00);
    expect((float) $invoice->total_amount)->toBe(275.00);
    expect(InvoiceLineItem::query()->where('invoice_id', $invoice->id)->count())->toBe(0);
});

it('validates required fields on create', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);

    Livewire::actingAs($user)
        ->test(Form::class)
        ->set('vendor_name', '')
        ->set('invoice_date', '')
        ->call('save')
        ->assertHasErrors(['vendor_name', 'invoice_date', 'project_id']);
});

// ---------------------------------------------------------------------------
// Show
// ---------------------------------------------------------------------------

it('shows invoice details to authorised users', function (): void {
    $user = userWithInvoicePermissions(['invoices.view']);
    $invoice = Invoice::factory()->for(Project::factory())->create([
        'vendor_name' => 'Detailed Vendor',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('Detailed Vendor');
});

// ---------------------------------------------------------------------------
// Status transitions
// ---------------------------------------------------------------------------

it('allows verifying a pending invoice', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.verify']);
    $invoice = Invoice::factory()->for(Project::factory())->pending()->create([
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['invoice' => $invoice])
        ->call('verify');

    expect($invoice->fresh()->status)->toBe(InvoiceStatusEnum::Verified);
});

it('allows marking a verified invoice as paid', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.mark-paid']);
    $invoice = Invoice::factory()->for(Project::factory())->verified()->create([
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['invoice' => $invoice])
        ->call('markAsPaid');

    expect($invoice->fresh()->status)->toBe(InvoiceStatusEnum::Paid);
    expect($invoice->fresh()->paid_at)->not->toBeNull();
});

it('prevents verifying without the verify permission', function (): void {
    $user = userWithInvoicePermissions(['invoices.view']);
    $invoice = Invoice::factory()->for(Project::factory())->pending()->create([
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['invoice' => $invoice])
        ->call('verify')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Model helpers
// ---------------------------------------------------------------------------

it('correctly identifies overdue invoices', function (): void {
    $invoice = Invoice::factory()->for(Project::factory())->make([
        'status' => InvoiceStatusEnum::Pending->value,
        'due_date' => now()->subDay(),
    ]);

    expect($invoice->isOverdue())->toBeTrue();
});

it('does not mark paid invoices as overdue', function (): void {
    $invoice = Invoice::factory()->for(Project::factory())->make([
        'status' => InvoiceStatusEnum::Paid->value,
        'due_date' => now()->subDay(),
    ]);

    expect($invoice->isOverdue())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

/**
 * @param  array<int, string>  $permissions
 */
function userWithInvoicePermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'Invoice Test Role '.str()->uuid(),
        'description' => 'Role for invoice feature tests',
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
