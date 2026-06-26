<?php

namespace App\Domains\Invoices\Livewire\Admin\Invoices;

use App\Domains\Accounting\Models\AccountingCode;
use App\Domains\Invoices\Enums\InvoiceStatusEnum;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
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

    public ?Project $project = null;

    public bool $embedded = false;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'project')]
    public string $projectFilter = '';

    #[Url(as: 'accounting')]
    public string $accountingCodeFilter = '';

    public function mount(?Project $project = null, bool $embedded = false): void
    {
        $this->authorize('viewAny', Invoice::class);

        $this->project = $project;
        $this->embedded = $embedded && $project instanceof Project;

        if ($this->embedded) {
            $this->projectFilter = (string) $project->id;
        }
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

    public function updatedAccountingCodeFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = $this->search;
        $statusFilter = $this->statusFilter;
        $projectFilter = $this->embedded && $this->project instanceof Project
            ? (string) $this->project->id
            : $this->projectFilter;
        $accountingCodeFilter = $this->accountingCodeFilter;

        $invoices = Invoice::query()
            ->with(['project', 'accountingCode', 'creator'])
            ->when($projectFilter !== '', fn (Builder $query) => $query->where('project_id', $projectFilter))
            ->when($accountingCodeFilter !== '', fn (Builder $query) => $query->where('accounting_code_id', $accountingCodeFilter))
            ->when($statusFilter !== '', fn (Builder $query) => $query->where('status', $statusFilter))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('vendor_name', 'like', '%'.$search.'%')
                    ->orWhere('invoice_number', 'like', '%'.$search.'%');
            }))
            ->latest('invoice_date')
            ->paginate(15);

        return view('invoices::livewire.admin.invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatusEnum::toArray(),
            'projects' => $this->embedded
                ? collect()
                : Project::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'project_number']),
            'accountingCodes' => AccountingCode::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'embeddedProject' => $this->embedded ? $this->project : null,
        ]);
    }
}
