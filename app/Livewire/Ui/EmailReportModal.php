<?php

namespace App\Livewire\Ui;

use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class EmailReportModal extends Component
{
    #[Modelable]
    public bool $open = false;

    public ?string $title = null;

    public function render(): View
    {
        return view('livewire.ui.email-report-modal');
    }
}
