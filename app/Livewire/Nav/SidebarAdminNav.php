<?php

namespace App\Livewire\Nav;

use Illuminate\View\View;
use Livewire\Component;

class SidebarAdminNav extends Component
{
    public function render(): View
    {
        return view('livewire.nav.admin.sidebar');
    }
}
