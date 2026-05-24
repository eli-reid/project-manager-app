<?php

namespace App\Core\Auth\User\Livewire\Layout;

use Illuminate\View\View;
use Livewire\Component;

class AppHeader extends Component
{
    public function render(): View
    {
        return view('auth-user::livewire.layout.app-header');
    }
}
