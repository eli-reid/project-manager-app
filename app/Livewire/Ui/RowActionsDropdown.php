<?php

namespace App\Livewire\Ui;

use Illuminate\View\View;
use Livewire\Component;

class RowActionsDropdown extends Component
{
    public string $label = 'Row actions';

    public string $width = 'w-40';

    public int $menuHeight = 160;

    public function render(): View
    {
        return view('livewire.ui.row-actions-dropdown');
    }
}
