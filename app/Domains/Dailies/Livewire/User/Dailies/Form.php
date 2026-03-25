<?php

namespace App\Domains\Dailies\Livewire\User\Dailies;

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Daily Report Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?DailyReport $dailyReport = null;

    public bool $isEdit = false;

    public function mount(?DailyReport $dailyReport = null): void
    {
        if ($dailyReport !== null && $dailyReport->exists) {
            $this->authorize('update', $dailyReport);
            $this->dailyReport = $dailyReport;
            $this->isEdit = true;

            return;
        }

        $this->authorize('create', DailyReport::class);
    }

    public function render()
    {
        return view('dailies::livewire.user.dailies.form');
    }
}
