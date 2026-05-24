<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class PayrollAdmin extends Component
{
    public function render(): View
    {
        return view('payroll::layouts.payroll-admin');
    }
}
