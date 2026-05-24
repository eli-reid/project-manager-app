<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class Head extends Component
{
    public ?string $title = null;

    public function render(): View
    {
        return view('livewire.layouts.head');
    }
}
