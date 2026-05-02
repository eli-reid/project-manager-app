<?php

namespace App\Domains\Invoices\Livewire\Admin\Invoices;

use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
#[Layout('stock::layouts.stock-invoices-admin')]
#[Title('Invoice Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Invoice $invoice = null;

    public bool $isEdit = false;

    public string $project_id = '';

    public string $vendor_name = '';

    public string $invoice_number = '';

    public string $invoice_date = '';

    public string $due_date = '';

    public string $status = 'pending';

    public string $notes = '';

    public string $subtotal = '0.00';

    public string $tax_amount = '0.00';

    public string $total_amount = '0.00';

    /** @var array<int, array<string, mixed>> */
    public array $lineItems = [];

    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice !== null && $invoice->exists) {
            $this->authorize('update', $invoice);

            $this->invoice = $invoice;
            $this->isEdit = true;
            $this->project_id = $invoice->project_id;
            $this->vendor_name = $invoice->vendor_name;
            $this->invoice_number = $invoice->invoice_number ?? '';
            $this->invoice_date = $invoice->invoice_date->format('Y-m-d');
            $this->due_date = $invoice->due_date?->format('Y-m-d') ?? '';
            $this->status = $invoice->status?->value ?? 'pending';
            $this->notes = $invoice->notes ?? '';
            $this->subtotal = (string) $invoice->subtotal;
            $this->tax_amount = (string) $invoice->tax_amount;
            $this->total_amount = (string) $invoice->total_amount;
            $this->lineItems = $invoice->lineItems->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'total' => (string) $item->total,
                'sort_order' => $item->sort_order,
            ])->toArray();

            return;
        }

        $this->authorize('create', Invoice::class);
        $this->lineItems = [];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankLineItem(): array
    {
        return ['description' => '', 'quantity' => '1', 'unit_price' => '', 'total' => '0.00', 'sort_order' => 0];
    }

    public function addLineItem(): void
    {
        $this->lineItems[] = $this->blankLineItem();
    }

    public function removeLineItem(int $index): void
    {
        array_splice($this->lineItems, $index, 1);
        $this->lineItems = array_values($this->lineItems);

        if (! empty($this->lineItems)) {
            $this->recalculateTotalsFromLineItems();

            return;
        }

        $this->recalculateTotalAmount();
    }

    public function updatedLineItems(mixed $value, ?string $key): void
    {
        if ($key !== null && (str_ends_with($key, '.quantity') || str_ends_with($key, '.unit_price'))) {
            $this->recalculateTotalsFromLineItems();
        }
    }

    public function updatedSubtotal(): void
    {
        if (! empty($this->lineItems)) {
            $this->recalculateTotalsFromLineItems();

            return;
        }

        $this->recalculateTotalAmount();
    }

    public function updatedTaxAmount(): void
    {
        if (! empty($this->lineItems)) {
            $this->recalculateTotalsFromLineItems();

            return;
        }

        $this->recalculateTotalAmount();
    }

    private function recalculateTotalsFromLineItems(): void
    {
        if (empty($this->lineItems)) {
            $this->recalculateTotalAmount();

            return;
        }

        foreach ($this->lineItems as $i => $item) {
            $qty = (float) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $this->lineItems[$i]['total'] = number_format(round($qty * $price, 2), 2, '.', '');
        }

        $subtotal = array_sum(array_column($this->lineItems, 'total'));
        $this->subtotal = number_format($subtotal, 2, '.', '');
        $this->recalculateTotalAmount();
    }

    private function recalculateTotalAmount(): void
    {
        $subtotal = (float) ($this->subtotal ?: 0);
        $taxAmount = (float) ($this->tax_amount ?: 0);
        $this->total_amount = number_format($subtotal + $taxAmount, 2, '.', '');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizedLineItems(): array
    {
        return array_values(array_filter(
            $this->lineItems,
            fn (array $item): bool => filled(trim((string) ($item['description'] ?? '')))
                || filled((string) ($item['unit_price'] ?? ''))
        ));
    }

    protected function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'vendor_name' => ['required', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'status' => ['required', 'in:'.implode(',', array_keys(InvoiceStatusEnum::toArray()))],
            'notes' => ['nullable', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'lineItems' => ['nullable', 'array'],
            'lineItems.*.description' => ['required', 'string', 'max:255'],
            'lineItems.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lineItems.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lineItems.*.total' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function save(): void
    {
        $this->lineItems = $this->normalizedLineItems();

        if (! empty($this->lineItems)) {
            $this->recalculateTotalsFromLineItems();
        } else {
            $this->recalculateTotalAmount();
        }

        $validated = $this->validate();

        $invoiceData = [
            'project_id' => $validated['project_id'],
            'vendor_name' => $validated['vendor_name'],
            'invoice_number' => filled($validated['invoice_number']) ? $validated['invoice_number'] : null,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => filled($validated['due_date']) ? $validated['due_date'] : null,
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'],
            'total_amount' => $validated['total_amount'],
            'status' => $validated['status'],
            'notes' => filled($validated['notes']) ? $validated['notes'] : null,
        ];

        DB::transaction(function () use ($invoiceData, $validated): void {
            if ($this->isEdit) {
                $invoice = $this->invoice;
                if ($invoice === null) {
                    return;
                }

                $this->authorize('update', $invoice);
                $invoice->update($invoiceData);
                $invoice->lineItems()->delete();

                foreach ($validated['lineItems'] as $i => $item) {
                    $invoice->lineItems()->create([
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total'],
                        'sort_order' => $i,
                    ]);
                }

                session()->flash('success', 'Invoice updated successfully.');
                $this->redirectRoute('admin.invoices.show', $invoice, navigate: true);

                return;
            }

            $this->authorize('create', Invoice::class);
            $invoice = Invoice::query()->create([
                ...$invoiceData,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['lineItems'] as $i => $item) {
                $invoice->lineItems()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'sort_order' => $i,
                ]);
            }

            session()->flash('success', 'Invoice created successfully.');
            $this->redirectRoute('admin.invoices.show', $invoice, navigate: true);
        });
    }

    public function render()
    {
        return view('invoices::livewire.admin.invoices.form', [
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'project_number']),
            'statuses' => InvoiceStatusEnum::toArray(),
        ]);
    }
}
