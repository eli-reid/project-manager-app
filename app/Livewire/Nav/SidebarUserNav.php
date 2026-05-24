<?php

namespace App\Livewire\Nav;

use Illuminate\View\View;
use Livewire\Component;

class SidebarUserNav extends Component
{
    public function render(): View
    {
        return view('livewire.nav.sidebar-user-nav');
    }
}
