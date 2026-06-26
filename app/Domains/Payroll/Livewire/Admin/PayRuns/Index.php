<?php

namespace App\Domains\Payroll\Livewire\Admin\PayRuns;

use App\Domains\Payroll\Models\PayRun;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('payroll::livewire.layouts.payroll-admin')]
#[Title('Payroll Pay Runs')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function mount(): void
    {
        $this->authorize('payroll-runs.preview');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $runs = PayRun::query()
            ->with(['creator:id,first_name,last_name', 'approver:id,first_name,last_name'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('pay_period_end')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('payroll::livewire.admin.pay-runs.index', [
            'runs' => $runs,
        ]);
    }
}
