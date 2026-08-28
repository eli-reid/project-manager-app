<?php

namespace App\Domains\Invoices\Livewire\Admin\Invoices;

use App\Domains\Documents\Services\DocumentService;
use App\Domains\Invoices\Jobs\ProcessInvoicePdfJob;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Models\InvoicePdfImport;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('stock::livewire.layouts.stock-invoices-admin')]
#[Title('Import Invoices from PDF')]
class PdfImport extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public const MAX_FILES = 10;

    public const MAX_FILE_SIZE_KB = 10240;

    public string $project_id = '';

    /** @var array<int, mixed> */
    public array $files = [];

    public bool $uploaded = false;

    public bool $reviewing = false;

    /** @var array<int, string> Ids of InvoicePdfImport rows created for this batch */
    public array $importIds = [];

    /** @var array<int, array<string, mixed>> Editable review rows, keyed by import id index */
    public array $reviewRows = [];

    public function mount(): void
    {
        $this->authorize('create', Invoice::class);
    }

    protected function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'files.*' => ['file', 'mimes:pdf', 'max:'.self::MAX_FILE_SIZE_KB],
        ];
    }

    public function upload(): void
    {
        $this->authorize('create', Invoice::class);

        $this->validate();

        $importIds = [];

        foreach ($this->files as $file) {
            $storedPath = $file->store('invoice-imports', 'local');

            $import = InvoicePdfImport::query()->create([
                'project_id' => $this->project_id,
                'created_by' => Auth::id(),
                'file_path' => $storedPath,
                'status' => InvoicePdfImport::STATUS_PENDING,
            ]);

            $importIds[] = $import->id;

            ProcessInvoicePdfJob::dispatch(
                $import->id,
                Storage::disk('local')->path($storedPath),
                $this->project_id,
                (string) Auth::id(),
            );
        }

        $this->importIds = $importIds;
        $this->uploaded = true;
        $this->files = [];
    }

    public function pollStatus(): void
    {
        if (! $this->uploaded || $this->reviewing) {
            return;
        }

        $imports = InvoicePdfImport::query()->whereIn('id', $this->importIds)->get();

        $allFinished = $imports->every(fn (InvoicePdfImport $import): bool => $import->isParsed() || $import->isFailed());

        if ($allFinished && $imports->isNotEmpty()) {
            $this->buildReviewRows($imports);
            $this->reviewing = true;
        }
    }

    /**
     * @param  Collection<int, InvoicePdfImport>  $imports
     */
    private function buildReviewRows($imports): void
    {
        $rows = [];

        foreach ($imports as $import) {
            if (! $import->isParsed()) {
                continue;
            }

            $parsed = $import->parsed_data ?? [];

            $rows[] = [
                'import_id' => $import->id,
                'selected' => true,
                'vendor_name' => $parsed['vendor_name'] ?? '',
                'invoice_number' => $parsed['invoice_number'] ?? '',
                'invoice_date' => $parsed['invoice_date'] ?? '',
                'due_date' => $parsed['due_date'] ?? '',
                'subtotal' => $parsed['subtotal'] ?? '0.00',
                'tax_amount' => $parsed['tax_amount'] ?? '0.00',
                'total_amount' => $parsed['total_amount'] ?? '0.00',
                'line_items' => $parsed['line_items'] ?? [],
                'confidence' => $parsed['confidence'] ?? [],
            ];
        }

        $this->reviewRows = $rows;
    }

    public function removeReviewRow(int $index): void
    {
        unset($this->reviewRows[$index]);
        $this->reviewRows = array_values($this->reviewRows);
    }

    public function batchImport(DocumentService $documentService): void
    {
        $this->authorize('create', Invoice::class);

        $selectedRows = array_values(array_filter($this->reviewRows, fn (array $row): bool => (bool) ($row['selected'] ?? false)));

        if ($selectedRows === []) {
            $this->addError('reviewRows', 'Select at least one invoice to import.');

            return;
        }

        $imported = 0;

        DB::transaction(function () use ($selectedRows, $documentService, &$imported): void {
            foreach ($selectedRows as $row) {
                $import = InvoicePdfImport::query()->find($row['import_id']);

                if ($import === null || $import->isImported()) {
                    continue;
                }

                $invoice = Invoice::query()->create([
                    'project_id' => $import->project_id,
                    'vendor_name' => $row['vendor_name'] !== '' ? $row['vendor_name'] : 'Unknown Vendor',
                    'invoice_number' => $row['invoice_number'] !== '' ? $row['invoice_number'] : null,
                    'invoice_date' => $row['invoice_date'] !== '' ? $row['invoice_date'] : now()->toDateString(),
                    'due_date' => $row['due_date'] !== '' ? $row['due_date'] : null,
                    'subtotal' => $row['subtotal'] ?: 0,
                    'tax_amount' => $row['tax_amount'] ?: 0,
                    'total_amount' => $row['total_amount'] ?: 0,
                    'status' => 'pending',
                    'created_by' => Auth::id(),
                ]);

                foreach ((array) $row['line_items'] as $i => $item) {
                    $invoice->lineItems()->create([
                        'description' => $item['description'] ?? '',
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total' => $item['total'] ?? 0,
                        'sort_order' => $i,
                    ]);
                }

                if (Storage::disk('local')->exists($import->file_path)) {
                    $uploadedFile = new UploadedFile(
                        Storage::disk('local')->path($import->file_path),
                        basename((string) $import->file_path),
                        'application/pdf',
                        null,
                        true,
                    );

                    $documentService->uploadProjectDocument($invoice->project, Auth::user(), $uploadedFile, [
                        'title' => 'Invoice PDF - '.($row['vendor_name'] ?: 'Unknown Vendor'),
                    ]);
                }

                $import->update(['status' => InvoicePdfImport::STATUS_IMPORTED]);
                $imported++;
            }
        });

        session()->flash('success', $imported.' invoice(s) imported successfully.');
        $this->redirectRoute('admin.invoices.index', navigate: true);
    }

    public function render()
    {
        return view('invoices::livewire.admin.invoices.pdf-import', [
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'project_number']),
        ]);
    }
}
