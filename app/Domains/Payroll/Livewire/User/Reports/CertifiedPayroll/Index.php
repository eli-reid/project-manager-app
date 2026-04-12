<?php

namespace App\Domains\Payroll\Livewire\User\Reports\CertifiedPayroll;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Certified Payroll (WH-347)')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.certified-payroll.index');
    }
}
