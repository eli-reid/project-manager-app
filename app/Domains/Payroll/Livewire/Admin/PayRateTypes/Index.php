<?php

namespace App\Domains\Payroll\Livewire\Admin\PayRateTypes;

use App\Domains\Payroll\Models\PayRateType;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Payroll Rate Types')]
class Index extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'active';

    public function mount(): void
    {
        $this->authorize('payroll-rates.view');
    }

    public function updatedSearch(): void
    {
        // Full-page component; reset hooks can be added when pagination is introduced.
    }

    public function updatedStatusFilter(): void
    {
        // Full-page component; reset hooks can be added when pagination is introduced.
    }

    public function render(): View
    {
        $search = trim($this->search);
        $statusFilter = $this->statusFilter;

        $types = PayRateType::query()
            ->withCount('payRates')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('key', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('payroll::livewire.admin.pay-rate-types.index', [
            'types' => $types,
        ]);
    }
}
