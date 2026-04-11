<?php

namespace App\Domains\Reports\Livewire\User\OperationalReports;

use App\Domains\Reports\Services\ReportRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Operational Reports')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('reports.operational.view');
    }

    public function render()
    {
        return view('reports::livewire.user.operational-reports.index', [
            'reportCards' => app(ReportRegistry::class)->forSection('operational'),
        ]);
    }
}
