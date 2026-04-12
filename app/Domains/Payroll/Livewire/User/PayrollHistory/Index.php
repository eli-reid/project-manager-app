<?php

namespace App\Domains\Payroll\Livewire\User\PayrollHistory;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payroll History')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('payroll.view');
    }

    public function render(): View
    {
        return view('payroll::livewire.user.payroll-history.index');
    }
}
