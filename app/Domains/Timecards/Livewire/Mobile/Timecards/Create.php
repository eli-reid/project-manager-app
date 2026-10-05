<?php

namespace App\Domains\Timecards\Livewire\Mobile\Timecards;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Opens the timecard for the requested week, creating a draft on load when one does not exist yet.
 */
#[Layout('layouts.mobile')]
#[Title('Create Timecard')]
class Create extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('create', Timecard::class);

        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $timecard = app(TimecardLifecycleService::class)
            ->findOrCreateForWeek($user, $this->requestedWeekStart());

        $this->redirectRoute('timecards.mobile.show', ['timecard' => $timecard], navigate: true);
    }

    public function render(): string
    {
        return <<<'HTML'
            <div class="px-4 py-10 text-center text-sm text-zinc-500">{{ __('Opening timecard…') }}</div>
        HTML;
    }

    private function requestedWeekStart(): Carbon
    {
        $weekService = app(TimecardWeekService::class);
        $requested = request()->query('week_starting');

        if (is_string($requested) && $requested !== '') {
            try {
                return $weekService->normalizeWeekStart($requested);
            } catch (Throwable) {
                // Fall back to the current week for malformed dates.
            }
        }

        return $weekService->currentWeekStart();
    }
}
