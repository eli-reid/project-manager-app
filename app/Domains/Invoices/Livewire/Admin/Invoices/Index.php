<?php

namespace App\Domains\Invoices\Livewire\Admin\Invoices;

use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('stock::livewire.layouts.stock-invoices-admin')]
#[Title('Invoices')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'project')]
    public string $projectFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Invoice::class);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    public function deleteInvoice(string $invoiceId): void
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);

        $this->authorize('delete', $invoice);

        $invoice->delete();
        $this->resetPage();

        session()->flash('success', 'Invoice deleted successfully.');
    }

    public function render()
    {
        $search = $this->search;
        $statusFilter = $this->statusFilter;
        $projectFilter = $this->projectFilter;

        return view('invoices::livewire.admin.invoices.index', [
            'invoices' => Invoice::query()
                ->with(['project', 'creator'])
                ->when($projectFilter !== '', fn ($q) => $q->where('project_id', $projectFilter))
                ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
                ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                    $q->where('vendor_name', 'like', '%'.$search.'%')
                        ->orWhere('invoice_number', 'like', '%'.$search.'%');
                }))
                ->latest('invoice_date')
                ->paginate(15),
            'statuses' => InvoiceStatusEnum::toArray(),
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'project_number']),
        ]);
    }
}
