<?php

namespace App\Domains\Invoices\Livewire\Admin\Invoices;

use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Models\Invoice;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('stock::layouts.stock-invoices-admin')]
#[Title('Invoice Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoice = $invoice;
    }

    public function verify(): void
    {
        $this->authorize('verify', $this->invoice);

        $this->invoice->update([
            'status' => InvoiceStatusEnum::Verified->value,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        session()->flash('success', 'Invoice verified successfully.');
        $this->invoice->refresh();
    }

    public function markAsPaid(): void
    {
        $this->authorize('markAsPaid', $this->invoice);

        $this->invoice->update([
            'status' => InvoiceStatusEnum::Paid->value,
            'payment_date' => now()->toDateString(),
            'paid_at' => now(),
        ]);

        session()->flash('success', 'Invoice marked as paid.');
        $this->invoice->refresh();
    }

    public function reject(): void
    {
        $this->authorize('reject', $this->invoice);

        $this->invoice->update([
            'status' => InvoiceStatusEnum::Rejected->value,
        ]);

        session()->flash('success', 'Invoice rejected.');
        $this->invoice->refresh();
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->invoice);

        $this->invoice->delete();

        session()->flash('success', 'Invoice deleted.');
        $this->redirectRoute('admin.invoices.index', navigate: true);
    }

    public function render()
    {
        return view('invoices::livewire.admin.invoices.show', [
            'invoice' => $this->invoice->load(['project', 'creator', 'verifier', 'lineItems']),
        ]);
    }
}
