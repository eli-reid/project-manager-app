<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Livewire\Admin\Invoices\Form;
use App\Domains\Invoices\Livewire\Admin\Invoices\Index;
use App\Domains\Invoices\Livewire\Admin\Invoices\Show;
use App\Domains\Invoices\Livewire\Admin\Projects\ProjectTab;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Models\InvoiceLineItem;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectTabDefinition;
use App\Domains\Projects\Services\ProjectTabLinkBuilder;
use App\Domains\Projects\Services\ProjectTabRegistry;
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

it('renders invoice index actions with row navigation exclusion markers', function (): void {
    $user = userWithInvoicePermissions(['invoices.view']);
    Invoice::factory()->for(Project::factory())->create([
        'vendor_name' => 'Navigation Marker Vendor',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.invoices.index'))
        ->assertSuccessful()
        ->assertSee('data-prevent-row-nav', false)
        ->assertSee('window.Livewire?.navigate', false);
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

it('filters invoices by project', function (): void {
    $user = userWithInvoicePermissions(['invoices.view']);
    $selectedProject = Project::factory()->create();
    $otherProject = Project::factory()->create();

    Invoice::factory()->for($selectedProject)->create([
        'vendor_name' => 'Selected Project Vendor',
        'created_by' => $user->id,
    ]);

    Invoice::factory()->for($otherProject)->create([
        'vendor_name' => 'Other Project Vendor',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('admin.invoices.index', ['project' => $selectedProject->id]))
        ->assertSuccessful()
        ->assertSee('Selected Project Vendor')
        ->assertDontSee('Other Project Vendor');
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

it('opens the invoice create form inside the project tab', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view', 'invoices.create']);
    $project = Project::factory()->create(['name' => 'Invoice Context Project']);
    $links = app(ProjectTabLinkBuilder::class);

    $this->actingAs($user)
        ->get($links->to($project, 'invoices'))
        ->assertSuccessful()
        ->assertSee($links->to($project, 'invoices', mode: 'create'))
        ->assertDontSee('href="'.route('admin.invoices.create').'"', false);

    $this->get($links->to($project, 'invoices', mode: 'create'))
        ->assertSuccessful()
        ->assertSeeLivewire(Form::class)
        ->assertSee('Create Invoice')
        ->assertSee('Invoice Context Project');
});

it('creates an invoice for the preselected project and returns to its invoices tab', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view', 'invoices.create']);
    $project = Project::factory()->create(['is_active' => false]);

    Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true])
        ->assertSet('project_id', $project->id)
        ->assertSee($project->name)
        ->assertDontSee('Select a project')
        ->assertSee('href="'.app(ProjectTabLinkBuilder::class)->to($project, 'invoices').'"', false)
        ->set('vendor_name', 'Project Tab Vendor')
        ->set('invoice_date', '2026-03-24')
        ->set('subtotal', '100.00')
        ->set('tax_amount', '10.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(app(ProjectTabLinkBuilder::class)->to($project, 'invoices'));

    $invoice = Invoice::query()->where('vendor_name', 'Project Tab Vendor')->sole();
    expect($invoice->project_id)->toBe($project->id)
        ->and($invoice->created_by)->toBe($user->id)
        ->and((float) $invoice->total_amount)->toBe(110.00);

    Livewire::actingAs($user)
        ->test(Index::class, ['project' => $project, 'embedded' => true])
        ->assertSee('Project Tab Vendor');
});

it('rejects changing the project when creating an invoice within a project tab', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.create']);
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true])
        ->set('project_id', $otherProject->id)
        ->set('vendor_name', 'Wrong Project Vendor')
        ->set('invoice_date', '2026-03-24')
        ->call('save')
        ->assertHasErrors(['project_id' => 'in']);

    expect(Invoice::query()->count())->toBe(0);
});

it('requires invoice creation and project access permissions for the embedded form', function (array $permissions): void {
    $user = userWithInvoicePermissions($permissions);
    $project = Project::factory()->create();

    Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true])
        ->assertForbidden();
})->with([
    'no invoice creation' => [['projects.view', 'invoices.view']],
    'no project access' => [['invoices.view', 'invoices.create']],
]);

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

it('resolves invoice create and edit panels through the project tab registry', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view']);
    $project = Project::factory()->create();
    $registry = app(ProjectTabRegistry::class);

    foreach (['create', 'edit'] as $mode) {
        $panels = $registry->tabPanels($project, $user, [
            'invoices' => [
                'modeParam' => 'invoiceMode',
                'mode' => $mode,
                'detailParam' => 'invoiceId',
                'detailId' => 'invoice-123',
                'isCreateMode' => $mode === 'create',
            ],
        ], activeTab: 'invoices');

        expect($panels)->toHaveCount(1)
            ->and($panels[0]['component'])->toBe('invoices::admin.invoices.form')
            ->and($panels[0]['props'])->toBe([
                'project' => $project,
                'embedded' => true,
                ...($mode === 'edit' ? ['invoiceId' => 'invoice-123'] : []),
            ])
            ->and($panels[0]['key'])->toBe(
                'project-invoices-tab-'.$project->id.'-'.$mode.($mode === 'edit' ? '-invoice-123' : '')
            );
    }
});

it('opens the invoice edit form inside the project tab using registered query parameters', function (string $modeParam): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view', 'invoices.create', 'invoices.edit']);
    $project = Project::factory()->create(['name' => 'Invoice Edit Context Project']);
    $invoice = Invoice::factory()->for($project)->pending()->create(['created_by' => $user->id]);

    ProjectTabDefinition::query()->updateOrCreate(
        ['key' => 'invoices'],
        ['label' => 'Invoices', 'sort_order' => 40, 'mode_query_param' => $modeParam, 'is_active' => true],
    );
    app()->forgetInstance(ProjectTabRegistry::class);
    app()->forgetInstance(ProjectTabLinkBuilder::class);

    $links = app(ProjectTabLinkBuilder::class);
    $editUrl = $links->to($project, 'invoices', mode: 'edit', detailId: $invoice->id);

    expect($editUrl)->toContain($modeParam.'=edit')->toContain('invoiceId='.$invoice->id);

    $this->actingAs($user)
        ->get($links->to($project, 'invoices'))
        ->assertSuccessful()
        ->assertSee($links->to($project, 'invoices', mode: 'create'))
        ->assertSee($editUrl)
        ->assertDontSee('href="'.route('admin.invoices.edit', $invoice).'"', false);

    $this->get($editUrl)
        ->assertSuccessful()
        ->assertSeeLivewire(Form::class)
        ->assertSee('Edit Invoice')
        ->assertSee('Invoice Edit Context Project')
        ->assertSee($invoice->vendor_name)
        ->assertSee('href="'.$links->to($project, 'invoices').'"', false)
        ->assertDontSee('Select a project');
})->with(['invoiceMode', 'invoiceAction']);

it('updates an invoice inside its project and returns to the invoices tab', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view', 'invoices.edit']);
    $project = Project::factory()->create(['is_active' => false]);
    $invoice = Invoice::factory()->for($project)->pending()->create(['created_by' => $user->id]);
    InvoiceLineItem::factory()->for($invoice)->create(['description' => 'Old line item']);

    Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true, 'invoiceId' => $invoice->id])
        ->assertSet('isEdit', true)
        ->assertSet('embedded', true)
        ->assertSet('project_id', $project->id)
        ->assertSet('vendor_name', $invoice->vendor_name)
        ->assertSee('href="'.app(ProjectTabLinkBuilder::class)->to($project, 'invoices').'"', false)
        ->assertDontSee('Select a project')
        ->set('vendor_name', 'Project Updated Vendor')
        ->set('tax_amount', '5.00')
        ->set('lineItems', [
            ['description' => 'Replacement item', 'quantity' => '2', 'unit_price' => '25.00', 'total' => '0.00', 'sort_order' => 0],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(app(ProjectTabLinkBuilder::class)->to($project, 'invoices'));

    $invoice->refresh();
    expect(Invoice::query()->count())->toBe(1)
        ->and($invoice->project_id)->toBe($project->id)
        ->and($invoice->vendor_name)->toBe('Project Updated Vendor')
        ->and((float) $invoice->total_amount)->toBe(55.00)
        ->and($invoice->lineItems()->sole()->description)->toBe('Replacement item');
});

it('rejects changing the project when editing an embedded invoice', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.edit']);
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $invoice = Invoice::factory()->for($project)->pending()->create(['created_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true, 'invoice' => $invoice])
        ->assertSet('embedded', true)
        ->set('project_id', $otherProject->id)
        ->set('vendor_name', 'Rejected Vendor')
        ->call('save')
        ->assertHasErrors(['project_id' => 'in']);

    expect($invoice->fresh()->project_id)->toBe($project->id)
        ->and($invoice->fresh()->vendor_name)->not->toBe('Rejected Vendor');
});

it('rejects invoice edit links with unrelated missing or deleted invoices', function (string $target): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view', 'invoices.edit']);
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->pending()->create(['created_by' => $user->id]);
    $invoiceId = match ($target) {
        'unrelated' => $invoice->id,
        'missing' => (string) str()->uuid(),
        'empty' => '',
        'deleted' => $invoice->id,
    };

    if ($target === 'deleted') {
        $invoice->update(['project_id' => $project->id]);
        $invoice->delete();
    }

    $this->actingAs($user)
        ->get(app(ProjectTabLinkBuilder::class)->to($project, 'invoices', mode: 'edit', detailId: $invoiceId))
        ->assertNotFound();
})->with(['unrelated', 'missing', 'empty', 'deleted']);

it('rejects directly embedding an invoice from another project', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.edit']);
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->pending()->create(['created_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true, 'invoice' => $invoice])
        ->assertNotFound();
});

it('requires invoice editing and project access permissions for the embedded edit form', function (array $permissions): void {
    $user = userWithInvoicePermissions($permissions);
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->for($project)->pending()->create(['created_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true, 'invoiceId' => $invoice->id])
        ->assertForbidden();
})->with([
    'no invoice editing' => [['projects.view', 'invoices.view']],
    'no project access' => [['invoices.view', 'invoices.edit']],
]);

it('preserves paid invoice edit restrictions inside the project tab', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view', 'invoices.edit']);
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->for($project)->paid()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(app(ProjectTabLinkBuilder::class)->to($project, 'invoices', mode: 'edit', detailId: $invoice->id))
        ->assertForbidden();
});

it('does not move an invoice back after it leaves the embedded project', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.edit']);
    $project = Project::factory()->create();
    $otherProject = Project::factory()->create();
    $invoice = Invoice::factory()->for($project)->pending()->create(['created_by' => $user->id]);

    $form = Livewire::actingAs($user)
        ->test(Form::class, ['project' => $project, 'embedded' => true, 'invoiceId' => $invoice->id]);

    $invoice->update(['project_id' => $otherProject->id]);

    $form->call('save')->assertNotFound();

    expect($invoice->fresh()->project_id)->toBe($otherProject->id);
});

it('keeps legacy project invoice edit links within the project view', function (): void {
    $user = userWithInvoicePermissions(['projects.view', 'invoices.view', 'invoices.edit']);
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->for($project)->pending()->create(['created_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(ProjectTab::class, [
            'project' => $project,
            'invoices' => collect([$invoice]),
            'invoiceCount' => 1,
        ])
        ->assertSee(app(ProjectTabLinkBuilder::class)->to($project, 'invoices', mode: 'edit', detailId: $invoice->id))
        ->assertDontSee('href="'.route('admin.invoices.edit', $invoice).'"', false);
});

it('updates an invoice with the edit permission', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.edit']);
    $invoice = Invoice::factory()->for(Project::factory())->pending()->create([
        'vendor_name' => 'Original Vendor',
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Form::class, ['invoice' => $invoice])
        ->assertSet('embedded', false)
        ->assertSee('Select a project')
        ->assertSee('href="'.route('admin.invoices.show', $invoice).'"', false)
        ->set('vendor_name', 'Corrected Vendor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.invoices.show', $invoice));

    expect($invoice->fresh()->vendor_name)->toBe('Corrected Vendor');
});

it('allows admins to edit invoices in every status', function (InvoiceStatusEnum $status): void {
    $admin = userWithInvoicePermissions(['invoices.view', 'invoices.edit']);
    $admin->update(['is_admin' => true]);
    $invoice = Invoice::factory()->for(Project::factory())->create([
        'status' => $status,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.invoices.edit', $invoice))
        ->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSee(route('admin.invoices.edit', $invoice), false);

    Livewire::actingAs($admin)
        ->test(Show::class, ['invoice' => $invoice])
        ->assertSee(route('admin.invoices.edit', $invoice), false);

    Livewire::actingAs($admin)
        ->test(Form::class, ['invoice' => $invoice])
        ->set('vendor_name', 'Admin Corrected Vendor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.invoices.show', $invoice));

    expect($invoice->fresh()->vendor_name)->toBe('Admin Corrected Vendor')
        ->and($invoice->fresh()->status)->toBe($status);
})->with(InvoiceStatusEnum::cases());

it('allows users with the built-in admin role to edit paid invoices', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.edit']);
    $role = Role::query()->where('name', Role::BUILT_IN_ADMIN)->firstOrFail();
    $user->roles()->attach($role);
    $user->flushAuthorizationCache();
    $invoice = Invoice::factory()->paid()->create(['created_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(Form::class, ['invoice' => $invoice])
        ->set('vendor_name', 'Role Admin Corrected Vendor')
        ->call('save')
        ->assertHasNoErrors();

    expect($invoice->fresh()->vendor_name)->toBe('Role Admin Corrected Vendor');
});

it('still prevents non-admin editors from editing paid invoices', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.edit']);
    $invoice = Invoice::factory()->paid()->create(['created_by' => $user->id]);

    Livewire::actingAs($user)
        ->test(Form::class, ['invoice' => $invoice])
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertDontSee(route('admin.invoices.edit', $invoice), false);
});

it('deletes an invoice with the delete permission from the invoice index', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.delete']);
    $invoice = Invoice::factory()->for(Project::factory())->pending()->create([
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteInvoice', $invoice->id);

    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
});

it('does not allow users without the edit permission to edit an invoice', function (): void {
    $user = userWithInvoicePermissions(['invoices.view']);
    $invoice = Invoice::factory()->for(Project::factory())->create([
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(Form::class, ['invoice' => $invoice])
        ->assertForbidden();
});

it('deletes an invoice from the project invoices tab', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.delete']);
    $project = Project::factory()->create();
    $invoice = Invoice::factory()->for($project)->pending()->create([
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(ProjectTab::class, [
            'project' => $project,
            'invoices' => collect([$invoice]),
            'invoiceCount' => 1,
        ])
        ->call('deleteInvoice', $invoice->id)
        ->assertSet('invoiceCount', 0)
        ->assertDontSee($invoice->vendor_name);

    $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
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
