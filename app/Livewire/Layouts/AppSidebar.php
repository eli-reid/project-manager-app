<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class AppSidebar extends Component
{
    public function render(): View
    {
        return view('livewire.layouts.app-sidebar');
    }
}
