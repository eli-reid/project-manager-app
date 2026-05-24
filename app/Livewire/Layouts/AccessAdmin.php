<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class AccessAdmin extends Component
{
    public function render(): View
    {
        return view('core-user::layouts.access-admin');
    }
}
