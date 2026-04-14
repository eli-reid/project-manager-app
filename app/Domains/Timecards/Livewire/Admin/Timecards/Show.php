<?php

namespace App\Domains\Timecards\Livewire\Admin\Timecards;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.time-management-admin')]
#[Title('Timecard Review')]
class Show extends Component
{
    use AuthorizesRequests;

    public Timecard $timecard;

    public ?string $rejectionReason = null;

    public function mount(Timecard $timecard): void
    {
        $this->authorize('view', $timecard);
        $this->timecard = $timecard->load(['user', 'entries.project', 'entries.user', 'approver', 'rejector']);
    }

    public function approve(): void
    {
        $this->authorize('approve', $this->timecard);
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $this->timecard = app(TimecardLifecycleService::class)->approve($this->timecard, $user);
        session()->flash('success', 'Timecard approved successfully.');
    }

    public function reject(): void
    {
        $this->authorize('reject', $this->timecard);
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 401);

        $this->timecard = app(TimecardLifecycleService::class)->reject($this->timecard, $user, $this->rejectionReason);
        $this->rejectionReason = null;
        session()->flash('success', 'Timecard rejected successfully.');
    }

    public function resetStatus(): void
    {
        $this->authorize('reset', $this->timecard);
        $this->timecard = app(TimecardLifecycleService::class)->resetToDraft($this->timecard);
        session()->flash('success', 'Timecard reset to draft.');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->timecard);

        app(TimecardLifecycleService::class)->delete($this->timecard);

        session()->flash('success', 'Timecard deleted successfully.');

        $this->redirectRoute('admin.timecards.index', navigate: true);
    }

    public function render()
    {
        return view('timecards::livewire.admin.timecards.show', [
            'timecard' => $this->timecard->fresh(['user', 'entries.project', 'entries.user', 'approver', 'rejector']),
        ]);
    }
}
