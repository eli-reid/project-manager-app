<?php

namespace App\Domains\Timecards\Livewire\Mobile\Timecards;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.mobile')]
#[Title('My Timecards')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Timecard::class);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $thisWeekStart = app(TimecardWeekService::class)->currentWeekStart();
        $lastWeekStart = $thisWeekStart->copy()->subWeek();

        $pinnedTimecards = Timecard::query()
            ->where('user_id', $user->id)
            ->whereDate('week_starting', '>=', $lastWeekStart->toDateString())
            ->whereDate('week_starting', '<=', $thisWeekStart->toDateString())
            ->withCount('entries')
            ->get()
            ->keyBy(fn (Timecard $timecard): string => $timecard->week_starting->toDateString());

        $pinnedWeeks = collect([
            ['label' => __('This Week'), 'start' => $thisWeekStart],
            ['label' => __('Last Week'), 'start' => $lastWeekStart],
        ])->map(fn (array $week): array => [
            ...$week,
            'end' => $week['start']->copy()->addDays(6),
            'timecard' => $pinnedTimecards->get($week['start']->toDateString()),
        ]);

        return view('timecards::livewire.mobile.timecards.index', [
            'pinnedWeeks' => $pinnedWeeks,
            'timecards' => Timecard::query()
                ->where('user_id', $user->id)
                ->where(function ($query) use ($lastWeekStart, $thisWeekStart): void {
                    $query->whereDate('week_starting', '<', $lastWeekStart->toDateString())
                        ->orWhereDate('week_starting', '>', $thisWeekStart->toDateString());
                })
                ->withCount('entries')
                ->latest('week_starting')
                ->paginate(15),
        ]);
    }
}
