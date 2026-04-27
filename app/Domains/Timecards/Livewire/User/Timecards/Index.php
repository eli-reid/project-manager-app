<?php

namespace App\Domains\Timecards\Livewire\User\Timecards;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
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

        $timecardWeekService = app(TimecardWeekService::class);

        return view('timecards::livewire.user.timecards.index', [
            'timecards' => Timecard::query()
                ->where('user_id', $user->id)
                ->withCount('entries')
                ->latest('week_starting')
                ->paginate(10),
            'futureWeeks' => $timecardWeekService->futureWeekOptions((string) $user->id, includePreviousWeek: true),
        ]);
    }
}
