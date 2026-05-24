<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class AppHeader extends Component
{
    public function render(): View
    {
        return view('livewire.layouts.app-header');
    }
}
