<?php

namespace App\Domains\Payroll\Livewire\User\Reports\TaxFilings;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payroll Tax Filings (941 and W-2)')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.tax-filings.index');
    }
}
