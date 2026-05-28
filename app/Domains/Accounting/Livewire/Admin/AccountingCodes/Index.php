<?php

namespace App\Domains\Accounting\Livewire\Admin\AccountingCodes;

use App\Domains\Accounting\Models\AccountingCode;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Accounting Codes')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'active')]
    public string $activeFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AccountingCode::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = $this->search;

        return view('accounting::livewire.admin.accounting-codes.index', [
            'accountingCodes' => AccountingCode::query()
                ->with(['parent:id,code,name,parent_id'])
                ->withCount(['projects', 'invoices', 'stockOrders'])
                ->withSum('invoices', 'total_amount')
                ->when($this->activeFilter !== '', function ($query): void {
                    $query->where('is_active', $this->activeFilter === '1');
                })
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder->where('code', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%')
                            ->orWhere('description', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('code')
                ->paginate(15),
        ]);
    }
}
