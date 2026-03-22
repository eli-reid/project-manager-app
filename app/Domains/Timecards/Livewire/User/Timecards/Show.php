<?php

namespace App\Domains\Timecards\Livewire\User\Timecards;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Timecard Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public Timecard $timecard;

    public function mount(Timecard $timecard): void
    {
        $this->authorize('view', $timecard);

        $this->timecard = $timecard->load(['entries.project']);
    }

    public function submit(): void
    {
        $this->authorize('submit', $this->timecard);

        $this->timecard = app(TimecardLifecycleService::class)->submit($this->timecard);
        session()->flash('success', 'Timecard submitted successfully.');
    }

    public function resetToDraft(): void
    {
        $this->authorize('reset', $this->timecard);

        $this->timecard = app(TimecardLifecycleService::class)->resetToDraft($this->timecard);
        session()->flash('success', 'Timecard reset to draft.');
    }

    public function render()
    {
        return view('timecards::livewire.user.timecards.show', [
            'timecard' => $this->timecard->fresh(['entries.project', 'entries.user']),
        ]);
    }
}
