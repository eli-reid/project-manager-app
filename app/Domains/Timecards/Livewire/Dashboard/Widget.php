<?php

namespace App\Domains\Timecards\Livewire\Dashboard;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Widget extends Component
{
    public function render(): View
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $weekService = app(TimecardWeekService::class);
        $currentWeekStart = $weekService->currentWeekStart();

        /** @var Collection<int, Timecard> $timecards */
        $timecards = Timecard::query()
            ->where('user_id', $user->id)
            ->where('week_starting', '>=', $currentWeekStart->subWeeks(3)->toDateString())
            ->orderByDesc('week_starting')
            ->limit(4)
            ->get();

        $currentWeekStart = $weekService->currentWeekStart();

        $currentTimecard = $timecards->first(
            fn (Timecard $t): bool => $t->week_starting instanceof Carbon && $t->week_starting->isSameDay($currentWeekStart)
        );

        return view('timecards::livewire.dashboard.widget', [
            'timecards' => $timecards,
            'currentWeekStart' => $currentWeekStart,
            'currentTimecard' => $currentTimecard,
        ]);
    }
}
