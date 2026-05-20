<?php

namespace App\Domains\Dailies\Livewire\User\Dailies;

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;

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

    private function isMobileRoute(): bool
    {
        return request()->routeIs('dailies.mobile.*');
    }

    public function render()
    {
        return view('dailies::livewire.user.dailies.show', [
            'dailyReport' => $this->dailyReport,
        ])->layout($this->isMobileRoute() ? 'layouts.mobile' : 'layouts.app');
    }
}
