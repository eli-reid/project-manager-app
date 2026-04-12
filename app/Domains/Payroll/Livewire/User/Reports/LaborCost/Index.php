<?php

namespace App\Domains\Payroll\Livewire\User\Reports\LaborCost;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payroll Labor Cost by Project and Cost Code')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.labor-cost.index');
    }
}
