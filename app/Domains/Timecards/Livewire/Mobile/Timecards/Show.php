<?php

namespace App\Domains\Timecards\Livewire\Mobile\Timecards;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.mobile')]
class Show extends Component
{
    use AuthorizesRequests;

    public Timecard $timecard;

    public function mount(Timecard $timecard): void
    {
        $this->authorize('view', $timecard);

        $this->timecard = $timecard;
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
        $timecard = $this->timecard->fresh(['entries.project', 'entries.costCode']);
        $user = Auth::user();

        $entriesByDate = $timecard->entries
            ->sortBy(fn (TimecardEntry $entry): string => sprintf(
                '%s|%s|%s',
                $entry->date?->toDateString(),
                $entry->start_time ?: '99:99',
                $entry->created_at?->format('YmdHisu'),
            ))
            ->groupBy(fn (TimecardEntry $entry): string => (string) $entry->date?->toDateString());

        $days = collect(range(0, 6))->map(function (int $offset) use ($timecard, $entriesByDate): array {
            $date = $timecard->week_starting->copy()->addDays($offset);
            $entries = $entriesByDate->get($date->toDateString(), collect())->values();

            return [
                'date' => $date,
                'entries' => $entries,
                'hours' => (float) $entries->sum('hours'),
            ];
        });

        $canEdit = $timecard->status === Timecard::STATUS_DRAFT && ($user?->can('update', $timecard) ?? false);

        return view('timecards::livewire.mobile.timecards.show', [
            'timecard' => $timecard,
            'days' => $days,
            'canEdit' => $canEdit,
            'canSubmit' => $user?->can('submit', $timecard) ?? false,
            'canReset' => $user?->can('reset', $timecard) ?? false,
        ])->title(__('Week of :date', ['date' => $timecard->week_starting->format('M j')]));
    }
}
