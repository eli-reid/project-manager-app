<?php

namespace App\Domains\Payroll\Livewire\Admin\Timecards;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use App\Domains\Payroll\Data\ValidationResult;
use App\Domains\Payroll\Services\PayPeriodService;
use App\Domains\Payroll\Services\TimecardDailyReconciliationService;
use App\Domains\Payroll\Services\TimecardEntryValidationService;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Payroll Timecard Review')]
class Review extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'week')]
    public string $weekStarting = '';

    #[Url(as: 'user')]
    public string $userFilter = '';

    #[Url(as: 'project')]
    public string $projectFilter = '';

    #[Url(as: 'issues')]
    public string $issuesOnly = '0';

    public function mount(PayPeriodService $payPeriodService): void
    {
        $this->authorize('payroll-timecards.view');

        if ($this->weekStarting === '') {
            $this->weekStarting = $payPeriodService->currentPeriodStart()->toDateString();
        }
    }

    public function render(
        TimecardEntryValidationService $validationService,
        TimecardDailyReconciliationService $reconciliationService,
        PayrollTimecardReadGateway $timecardReadGateway,
    ): View {
        $startDate = Carbon::parse($this->weekStarting)->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay();
        $projectFilter = $this->projectFilter !== '' ? $this->projectFilter : null;

        $entries = $timecardReadGateway->reviewEntriesForDateRange(
            startDate: $startDate,
            endDate: $endDate,
            userId: $this->userFilter !== '' ? $this->userFilter : null,
            projectId: $projectFilter,
        );

        $validationByEntryId = $entries->mapWithKeys(function (TimecardEntry $entry) use ($validationService): array {
            return [(string) $entry->id => $validationService->validate($entry)];
        });

        $reconciliationMismatchKeys = $this->reconciliationMismatchKeys($entries, $startDate, $endDate, $projectFilter, $reconciliationService);

        if ($this->issuesOnly === '1') {
            $entries = $entries->filter(function (TimecardEntry $entry) use ($validationByEntryId, $reconciliationMismatchKeys): bool {
                /** @var ValidationResult|null $result */
                $result = $validationByEntryId->get((string) $entry->id);

                if ($result instanceof ValidationResult && ($result->hasBlocks() || $result->hasWarnings())) {
                    return true;
                }

                return $this->entryHasReconciliationMismatch($entry, $reconciliationMismatchKeys);
            })->values();
        }

        return view('payroll::livewire.admin.timecards.review', [
            'entries' => $entries,
            'validationByEntryId' => $validationByEntryId,
            'reconciliationMismatchKeys' => $reconciliationMismatchKeys,
            'users' => User::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'projects' => Project::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'project_number']),
            'weekEnding' => $endDate->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    private function reconciliationMismatchKeys(
        Collection $entries,
        Carbon $startDate,
        Carbon $endDate,
        ?string $projectId,
        TimecardDailyReconciliationService $reconciliationService,
    ): Collection {
        $userIds = $entries->pluck('user_id')->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return $userIds->flatMap(function (string $userId) use ($startDate, $endDate, $projectId, $reconciliationService): Collection {
            return $reconciliationService
                ->reconcile($userId, $startDate, $endDate, $projectId)
                ->filter(fn ($row) => $row->isMismatch)
                ->map(function ($row): string {
                    $projectPart = $row->projectId ?? '';

                    return $row->date.'|'.$projectPart;
                });
        })->unique()->values();
    }

    private function entryHasReconciliationMismatch(TimecardEntry $entry, Collection $mismatchKeys): bool
    {
        $date = (string) optional($entry->date)->toDateString();
        $projectId = (string) ($entry->project_id ?? '');

        return $mismatchKeys->contains($date.'|'.$projectId)
            || $mismatchKeys->contains($date.'|');
    }
}
