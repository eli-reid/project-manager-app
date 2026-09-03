<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Invoices\Data\InvoicePdfData;
use App\Domains\Invoices\Jobs\ProcessInvoicePdfJob;
use App\Domains\Invoices\Livewire\Admin\Invoices\Index as InvoicesIndex;
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

it('links to the pdf import page from the invoices index', function (): void {
    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);

    Livewire::actingAs($user)
        ->test(InvoicesIndex::class)
        ->assertSee('Import from PDF')
        ->assertSee(route('admin.invoices.import'), escape: false);
});

it('hides the pdf import link from users without create permission', function (): void {
    $user = userWithPdfImportPermissions(['invoices.view']);

    Livewire::actingAs($user)
        ->test(InvoicesIndex::class)
        ->assertDontSee('Import from PDF');
});

it('accepts an uploaded pdf and dispatches a processing job', function (): void {
    Queue::fake();
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('files', [$file])
        ->call('startImport')
        ->assertHasNoErrors();

    expect(InvoicePdfImport::query()->count())->toBe(1);

    Queue::assertPushed(ProcessInvoicePdfJob::class);
});

it('processes queued jobs immediately when the run-on-import setting is enabled', function (): void {
    config(['queue.default' => 'database']);
    Settings::set('invoices.pdf_import.run_queue_synchronously', 'true');
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('files', [$file])
        ->call('startImport')
        ->assertHasNoErrors();

    $import = InvoicePdfImport::query()->sole();

    // Rather than waiting for the next scheduled queue run, the job should
    // have already been processed inline within the same request.
    expect($import->status)->not->toBe(InvoicePdfImport::STATUS_PENDING);
});

it('leaves jobs queued when the run-on-import setting is disabled', function (): void {
    config(['queue.default' => 'database']);
    Settings::set('invoices.pdf_import.run_queue_synchronously', 'false');
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('files', [$file])
        ->call('startImport')
        ->assertHasNoErrors();

    $import = InvoicePdfImport::query()->sole();

    expect($import->status)->toBe(InvoicePdfImport::STATUS_PENDING);
});

it('shows validation errors when uploading without a project', function (): void {
    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);

    $file = UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('files', [$file])
        ->call('startImport')
        ->assertHasErrors(['project_id']);
});

it('polls status and transitions to review once all imports are parsed', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
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

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/sample-invoice.pdf', 'fake-pdf-content');

    $import = importFor($user, $project);

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

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
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
// Review validation
// ---------------------------------------------------------------------------

it('rejects invalid review values instead of failing the transaction', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $import = importFor($user, $project);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$import->id])
        ->call('pollStatus')
        ->set('reviewRows.0.invoice_date', 'not-a-date')
        ->set('reviewRows.0.total_amount', 'abc')
        ->call('batchImport')
        ->assertHasErrors(['reviewRows.0.invoice_date', 'reviewRows.0.total_amount']);

    expect(Invoice::query()->count())->toBe(0);
    expect($import->refresh()->status)->toBe(InvoicePdfImport::STATUS_PARSED);
});

it('rejects a blank line item description', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $import = importFor($user, $project);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$import->id])
        ->call('pollStatus')
        ->set('reviewRows.0.line_items.0.description', '')
        ->call('batchImport')
        ->assertHasErrors(['reviewRows.0.line_items.0.description']);

    expect(Invoice::query()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Batch scoping / authorization
// ---------------------------------------------------------------------------

it('does not expose another users import during polling', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $otherUser = User::factory()->create();
    $project = Project::factory()->create();

    $foreignImport = importFor($otherUser, $project);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$foreignImport->id])
        ->call('pollStatus')
        ->assertSet('reviewing', false)
        ->assertSet('reviewRows', []);
});

it('refuses to import an import row belonging to another user', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $otherUser = User::factory()->create();
    $project = Project::factory()->create();

    $ownImport = importFor($user, $project);
    $foreignImport = importFor($otherUser, $project);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$ownImport->id])
        ->call('pollStatus')
        // Tamper: point the review row at someone else's import.
        ->set('reviewRows.0.import_id', $foreignImport->id)
        ->call('batchImport');

    expect(Invoice::query()->count())->toBe(0);
    expect($foreignImport->refresh()->status)->toBe(InvoicePdfImport::STATUS_PARSED);
});

// ---------------------------------------------------------------------------
// Storage cleanup
// ---------------------------------------------------------------------------

it('deletes the staged pdf when parsing fails', function (): void {
    Storage::fake('local');

    $project = Project::factory()->create();
    $user = User::factory()->create();

    Storage::disk('local')->put('invoice-imports/broken.pdf', 'not-a-pdf');

    $import = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/broken.pdf',
        'status' => InvoicePdfImport::STATUS_PENDING,
    ]);

    (new ProcessInvoicePdfJob($import->id, '/nonexistent/path.pdf', $project->id, $user->id))
        ->handle(new InvoicePdfParserService);

    expect($import->refresh()->status)->toBe(InvoicePdfImport::STATUS_FAILED);
    Storage::disk('local')->assertMissing('invoice-imports/broken.pdf');
});

it('deletes the staged pdf after a successful import', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/sample-invoice.pdf', 'fake-pdf-content');
    $import = importFor($user, $project);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$import->id])
        ->call('pollStatus')
        ->call('batchImport')
        ->assertRedirect(route('admin.invoices.index'));

    Storage::disk('local')->assertMissing('invoice-imports/sample-invoice.pdf');
});

it('prunes stale imports and their staged files', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/stale.pdf', 'content');
    Storage::disk('local')->put('invoice-imports/fresh.pdf', 'content');

    $stale = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/stale.pdf',
        'status' => InvoicePdfImport::STATUS_FAILED,
    ]);
    $stale->forceFill(['created_at' => now()->subDays(30)])->save();

    $fresh = InvoicePdfImport::query()->create([
        'project_id' => $project->id,
        'created_by' => $user->id,
        'file_path' => 'invoice-imports/fresh.pdf',
        'status' => InvoicePdfImport::STATUS_PARSED,
    ]);

    $this->artisan('invoices:prune-pdf-imports', ['--days' => 7])->assertSuccessful();

    Storage::disk('local')->assertMissing('invoice-imports/stale.pdf');
    Storage::disk('local')->assertExists('invoice-imports/fresh.pdf');

    expect(InvoicePdfImport::query()->find($stale->id))->toBeNull()
        ->and(InvoicePdfImport::query()->find($fresh->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Source PDF preview
// ---------------------------------------------------------------------------

it('streams the staged pdf to the uploader for review', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/sample-invoice.pdf', '%PDF-1.4 fake');
    $import = importFor($user, $project);

    $this->actingAs($user)
        ->get(route('admin.invoices.import.preview', $import->id))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('forbids previewing another users staged pdf', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $otherUser = User::factory()->create();
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/sample-invoice.pdf', '%PDF-1.4 fake');
    $foreignImport = importFor($otherUser, $project);

    $this->actingAs($user)
        ->get(route('admin.invoices.import.preview', $foreignImport->id))
        ->assertForbidden();
});

it('returns 404 when the staged pdf is missing', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    $import = importFor($user, $project);

    $this->actingAs($user)
        ->get(route('admin.invoices.import.preview', $import->id))
        ->assertNotFound();
});

it('forbids previewing without invoice create permission', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view']);
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/sample-invoice.pdf', '%PDF-1.4 fake');
    $import = importFor($user, $project);

    $this->actingAs($user)
        ->get(route('admin.invoices.import.preview', $import->id))
        ->assertForbidden();
});

it('shows a pdf preview link for each review row', function (): void {
    Storage::fake('local');

    $user = userWithPdfImportPermissions(['invoices.view', 'invoices.create']);
    $project = Project::factory()->create();

    Storage::disk('local')->put('invoice-imports/sample-invoice.pdf', '%PDF-1.4 fake');
    $import = importFor($user, $project);

    Livewire::actingAs($user)
        ->test(PdfImport::class)
        ->set('project_id', $project->id)
        ->set('uploaded', true)
        ->set('importIds', [$import->id])
        ->call('pollStatus')
        ->assertSet('reviewRows.0.file_name', 'sample-invoice.pdf')
        ->assertSee('View PDF')
        ->assertSee(route('admin.invoices.import.preview', $import->id), escape: false);
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

/**
 * Creates a parsed import row with a realistic parsed payload.
 */
function importFor(User $user, Project $project): InvoicePdfImport
{
    // DocumentService reads this setting; default is s3, unusable in tests.
    Settings::set('documents.storage_disk', 'local');

    return InvoicePdfImport::query()->create([
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
}

/**
 * @param  array<int, string>  $permissions
 */
function userWithPdfImportPermissions(array $permissions): User
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
