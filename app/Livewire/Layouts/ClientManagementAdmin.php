<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class ClientManagementAdmin extends Component
{
    public function render(): View
    {
        return view('clients::layouts.client-management-admin');
    }
}
