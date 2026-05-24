<?php

namespace App\Livewire\Layout;

use Illuminate\View\View;
use Livewire\Component;

class AppHeader extends Component
{
    public function render(): View
    {
        return view('livewire.layout.app-header');
    }
}
