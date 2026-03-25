<?php

namespace App\Domains\Dailies\Livewire\Admin\Dailies;

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Daily Report Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public DailyReport $dailyReport;

    public function mount(DailyReport $dailyReport): void
    {
        $this->authorize('view', $dailyReport);

        $this->dailyReport = $dailyReport->load(['project', 'user', 'submittedBy']);
    }

    public function render()
    {
        return view('dailies::livewire.admin.dailies.show', [
            'dailyReport' => $this->dailyReport,
        ]);
    }
}
