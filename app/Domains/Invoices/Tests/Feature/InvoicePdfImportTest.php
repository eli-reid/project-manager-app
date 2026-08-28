<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Invoices\Data\InvoicePdfData;
use App\Domains\Invoices\Jobs\ProcessInvoicePdfJob;
use App\Domains\Invoices\Livewire\Admin\Invoices\PdfImport;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Models\InvoicePdfImport;
use App\Domains\Invoices\Services\InvoicePdfParserService;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// PDF Parsing
// ---------------------------------------------------------------------------

it('extracts structured data from a sample invoice pdf', function (): void {
    $service = new InvoicePdfParserService;

    $data = $service->parse(base_path('tests/fixtures/sample-invoice.pdf'));

    expect($data)->toBeInstanceOf(InvoicePdfData::class)
        ->and($data->vendorName)->toBe('Acme Supply Co.')
        ->and($data->invoiceNumber)->toBe('INV-1234')
        ->and($data->invoiceDate)->toBe('2026-01-15')
        ->and($data->dueDate)->toBe('2026-02-15')
        ->and($data->subtotal)->toBe('50.00')
        ->and($data->taxAmount)->toBe('5.00')
        ->and($data->totalAmount)->toBe('55.00')
        ->and($data->lineItems)->toHaveCount(2)
        ->and($data->lineItems[0]['description'])->toBe('Widget A')
        ->and($data->confidenceFor('invoice_number'))->toBeGreaterThan(0.6);
});

// ---------------------------------------------------------------------------
// ProcessInvoicePdfJob
// ---------------------------------------------------------------------------

it('marks the import row as parsed on success', function (): void {
    Storage::fake('local');

    $project = Project::factory()->create();
    $user = User::factory()->create();

    $path = base_path('tests/fixtures/sample-invoice.pdf');
    $import = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/sample-invoice.pdf',
        'status' => InvoicePdfImport::STATUS_PENDING,
    ]);

    (new ProcessInvoicePdfJob($import->id, $path, $project->id, $user->id))->handle(new InvoicePdfParserService);

    $import->refresh();

    expect($import->status)->toBe(InvoicePdfImport::STATUS_PARSED)
        ->and($import->parsed_data['vendor_name'])->toBe('Acme Supply Co.')
        ->and($import->error_message)->toBeNull();
});

it('marks the import row as failed when parsing throws', function (): void {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $import = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/missing.pdf',
        'status' => InvoicePdfImport::STATUS_PENDING,
    ]);

    (new ProcessInvoicePdfJob($import->id, '/nonexistent/path.pdf', $project->id, $user->id))->handle(new InvoicePdfParserService);

    $import->refresh();

    expect($import->status)->toBe(InvoicePdfImport::STATUS_FAILED)
        ->and($import->error_message)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Livewire upload component
// ---------------------------------------------------------------------------

it('forbids access to the pdf import route without permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.invoices.import'))
        ->assertForbidden();
});

it('redirects guests from the pdf import route', function (): void {
    $this->get(route('admin.invoices.import'))
        ->assertRedirect(route('login'));
});

it('accepts an uploaded pdf and dispatches a processing job', function (): void {
    Queue::fake();
    Storage::fake('local');

    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('files', [$file])
        ->call('upload')
        ->assertHasNoErrors();

    expect(InvoicePdfImport::query()->count())->toBe(1);

    Queue::assertPushed(ProcessInvoicePdfJob::class);
});

it('shows validation errors when uploading without a project', function (): void {
    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('files', [$file])
        ->call('upload')
        ->assertHasErrors(['project_id']);
});

it('polls status and transitions to review once all imports are parsed', function (): void {
    Storage::fake('local');

    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $import = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/sample-invoice.pdf',
        'status' => InvoicePdfImport::STATUS_PARSED,
        'parsed_data' => [
            'vendor_name' => 'Acme Supply Co.',
            'invoice_number' => 'INV-1234',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'subtotal' => '50.00',
            'tax_amount' => '5.00',
            'total_amount' => '55.00',
            'line_items' => [],
            'confidence' => ['vendor_name' => 0.9],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$import->id])
        ->call('pollStatus')
        ->assertSet('reviewing', true);
});

// ---------------------------------------------------------------------------
// Review + batch import
// ---------------------------------------------------------------------------

it('creates invoices and line items from confirmed review rows', function (): void {
    Storage::fake('local');

    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/sample-invoice.pdf', 'fake-pdf-content');

    $import = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/sample-invoice.pdf',
        'status' => InvoicePdfImport::STATUS_PARSED,
        'parsed_data' => [
            'vendor_name' => 'Acme Supply Co.',
            'invoice_number' => 'INV-1234',
            'invoice_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'subtotal' => '50.00',
            'tax_amount' => '5.00',
            'total_amount' => '55.00',
            'line_items' => [
                ['description' => 'Widget A', 'quantity' => '2', 'unit_price' => '10.00', 'total' => '20.00'],
            ],
            'confidence' => [],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$import->id])
        ->call('pollStatus')
        ->call('batchImport')
        ->assertRedirect(route('admin.invoices.index'));

    $invoice = Invoice::query()->where('vendor_name', 'Acme Supply Co.')->first();

    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->total_amount)->toBe(55.0)
        ->and($invoice->lineItems)->toHaveCount(1);

    $import->refresh();
    expect($import->status)->toBe(InvoicePdfImport::STATUS_IMPORTED);
});

it('allows removing a row from the review batch before import', function (): void {
    Storage::fake('local');

    $user = userWithInvoicePermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $import = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/sample-invoice.pdf',
        'status' => InvoicePdfImport::STATUS_PARSED,
        'parsed_data' => [
            'vendor_name' => 'Acme Supply Co.',
            'line_items' => [],
            'confidence' => [],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$import->id])
        ->call('pollStatus')
        ->call('removeReviewRow', 0)
        ->assertSet('reviewRows', []);
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
        'name' => 'Invoice PDF Test Role '.str()->uuid(),
        'description' => 'Role for invoice pdf import feature tests',
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
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->attach($role);

    return $user;
}
