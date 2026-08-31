<?php

namespace App\Domains\Invoices\Livewire\Admin\Invoices;

use App\Domains\Documents\Services\DocumentService;
use App\Domains\Invoices\Jobs\ProcessInvoicePdfJob;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Models\InvoicePdfImport;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

    /** @var array<int, array<string, mixed>> Editable review rows, one per parsed import */
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

    /**
     * Imports are always scoped to the current batch AND the authenticated
     * creator so a tampered request cannot reach another user's rows.
     *
     * @return Builder<InvoicePdfImport>
     */
    private function batchQuery(): Builder
    {
        return InvoicePdfImport::query()
            ->whereIn('id', $this->importIds)
            ->where('created_by', Auth::id());
    }

    public function startImport(): void
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

        $imports = $this->batchQuery()->get();

        $allFinished = $imports->every(fn (InvoicePdfImport $import): bool => $import->isParsed() || $import->isFailed());

        if ($allFinished && $imports->isNotEmpty()) {
            $this->buildReviewRows($imports);
            $this->reviewing = true;
        }
    }

    /**
     * @param  Collection<int, InvoicePdfImport>  $imports
     */
    private function buildReviewRows(Collection $imports): void
    {
        $rows = [];

        foreach ($imports as $import) {
            if (! $import->isParsed()) {
                continue;
            }

            $parsed = $import->parsed_data ?? [];

            $rows[] = [
                'import_id' => $import->id,
                'file_name' => basename((string) $import->file_path),
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

    /**
     * Drops line items that the reviewer blanked out entirely.
     *
     * @param  array<int, array<string, mixed>>  $lineItems
     * @return array<int, array<string, mixed>>
     */
    private function normalizedLineItems(array $lineItems): array
    {
        return array_values(array_filter(
            $lineItems,
            fn (array $item): bool => filled(trim((string) ($item['description'] ?? '')))
                || filled((string) ($item['unit_price'] ?? ''))
        ));
    }

    /**
     * Validates every selected row, mapping failures back onto the row index so
     * the reviewer sees inline errors instead of a failed transaction.
     *
     * @param  array<int, int>  $selectedIndexes
     */
    private function validateSelectedRows(array $selectedIndexes): bool
    {
        $rules = [
            'vendor_name' => ['required', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'tax_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'total_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'line_items.*.total' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];

        $isValid = true;

        foreach ($selectedIndexes as $index) {
            $row = $this->reviewRows[$index];
            $row['line_items'] = $this->normalizedLineItems((array) ($row['line_items'] ?? []));
            $this->reviewRows[$index]['line_items'] = $row['line_items'];

            $validator = Validator::make($row, $rules);

            if ($validator->fails()) {
                $isValid = false;

                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError('reviewRows.'.$index.'.'.$field, $message);
                    }
                }
            }
        }

        return $isValid;
    }

    public function batchImport(DocumentService $documentService): void
    {
        $this->authorize('create', Invoice::class);

        $selectedIndexes = [];

        foreach ($this->reviewRows as $index => $row) {
            if ((bool) ($row['selected'] ?? false)) {
                $selectedIndexes[] = $index;
            }
        }

        if ($selectedIndexes === []) {
            $this->addError('reviewRows', 'Select at least one invoice to import.');

            return;
        }

        if (! $this->validateSelectedRows($selectedIndexes)) {
            return;
        }

        // Only imports belonging to this batch and this user may be imported.
        $imports = $this->batchQuery()->get()->keyBy('id');

        $importedFilePaths = [];
        $imported = 0;

        DB::transaction(function () use ($selectedIndexes, $imports, $documentService, &$imported, &$importedFilePaths): void {
            foreach ($selectedIndexes as $index) {
                $row = $this->reviewRows[$index];
                $import = $imports->get($row['import_id']);

                if ($import === null || ! $import->isParsed()) {
                    continue;
                }

                $invoice = Invoice::query()->create([
                    'project_id' => $import->project_id,
                    'vendor_name' => $row['vendor_name'],
                    'invoice_number' => filled($row['invoice_number']) ? $row['invoice_number'] : null,
                    'invoice_date' => $row['invoice_date'],
                    'due_date' => filled($row['due_date']) ? $row['due_date'] : null,
                    'subtotal' => $row['subtotal'],
                    'tax_amount' => $row['tax_amount'],
                    'total_amount' => $row['total_amount'],
                    'status' => 'pending',
                    'created_by' => Auth::id(),
                ]);

                foreach ($row['line_items'] as $i => $item) {
                    $invoice->lineItems()->create([
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total'],
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
                        'title' => 'Invoice PDF - '.$row['vendor_name'],
                    ]);

                    $importedFilePaths[] = $import->file_path;
                }

                $import->update(['status' => InvoicePdfImport::STATUS_IMPORTED]);
                $imported++;
            }
        });

        // The source PDF now lives in the Documents domain, so the staged copy
        // is redundant. Deleted after commit so a rollback keeps the original.
        foreach ($importedFilePaths as $path) {
            Storage::disk('local')->delete($path);
        }

        session()->flash('success', $imported.' invoice(s) imported successfully.');
        $this->redirectRoute('admin.invoices.index', navigate: true);
    }

    public function render()
    {
        $imports = collect();

        if ($this->uploaded && ! $this->reviewing) {
            $imports = $this->batchQuery()->get()->keyBy('id');
        }

        return view('invoices::livewire.admin.invoices.pdf-import', [
            'imports' => $imports,
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'project_number']),
        ]);
    }
}
