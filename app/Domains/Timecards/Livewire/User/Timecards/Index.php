<?php

namespace App\Domains\Timecards\Livewire\User\Timecards;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardLifecycleService;
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

    public function createForWeek(string $weekStarting): void
    {
        $this->authorize('create', Timecard::class);

        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $timecardWeekService = app(TimecardWeekService::class);
        $normalizedWeekStart = $timecardWeekService->normalizeWeekStart($weekStarting);

        $existingTimecard = Timecard::query()
            ->where('user_id', $user->id)
            ->whereDate('week_starting', $normalizedWeekStart->toDateString())
            ->first();

        if ($existingTimecard instanceof Timecard) {
            $this->redirectRoute('timecards.edit', ['timecard' => $existingTimecard], navigate: true);

            return;
        }

        $timecard = app(TimecardLifecycleService::class)
            ->createDraftForUser($user, $normalizedWeekStart);

        session()->flash('success', 'Timecard draft created successfully.');

        $this->redirectRoute('timecards.edit', ['timecard' => $timecard], navigate: true);
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
