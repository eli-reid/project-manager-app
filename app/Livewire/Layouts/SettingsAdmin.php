<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class SettingsAdmin extends Component
{
    public function render(): View
    {
        return view('core::layouts.settings-admin');
    }
}
