<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class TimeManagementAdmin extends Component
{
    public function render(): View
    {
        return view('timecards::layouts.time-management-admin');
    }
}
