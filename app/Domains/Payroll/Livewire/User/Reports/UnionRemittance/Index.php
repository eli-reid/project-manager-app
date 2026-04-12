<?php

namespace App\Domains\Payroll\Livewire\User\Reports\UnionRemittance;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Union Remittance')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.union-remittance.index');
    }
}
