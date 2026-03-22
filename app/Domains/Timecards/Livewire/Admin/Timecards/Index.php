<?php

namespace App\Domains\Timecards\Livewire\Admin\Timecards;

use App\Core\User\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Timecards')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'user')]
    public string $userFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    /**
     * @var array<int, string>
     */
    public array $selectedTimecardIds = [];

    public bool $selectPage = false;

    public string $bulkAction = '';

    public ?string $bulkRejectionReason = null;

    public function updatingStatusFilter(): void
    {
        $this->resetBulkSelection();
        $this->resetPage();
    }

    public function updatingUserFilter(): void
    {
        $this->resetBulkSelection();
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetBulkSelection();
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetBulkSelection();
        $this->resetPage();
    }

    public function updatedSelectPage(bool $value): void
    {
        if (! $value) {
            $this->selectedTimecardIds = [];

            return;
        }

        $this->selectedTimecardIds = $this->baseQuery()
            ->latest('week_starting')
            ->forPage($this->getPage(), 15)
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }

    public function updatedSelectedTimecardIds(): void
    {
        $currentPageCount = $this->baseQuery()
            ->latest('week_starting')
            ->forPage($this->getPage(), 15)
            ->count();

        $this->selectPage = $currentPageCount > 0 && count($this->selectedTimecardIds) === $currentPageCount;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Timecard::class);
    }

    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->userFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetBulkSelection();
        $this->resetPage();
    }

    public function applyBulkAction(): void
    {
        $this->validate([
            'bulkAction' => ['required', 'in:approve,reject,delete'],
            'selectedTimecardIds' => ['required', 'array', 'min:1'],
            'selectedTimecardIds.*' => ['string', 'exists:timecards,id'],
            'bulkRejectionReason' => ['nullable', 'string', 'max:1000', 'required_if:bulkAction,reject'],
        ]);

        $actor = Auth::user();
        abort_unless($actor !== null, 401);

        $timecards = Timecard::query()
            ->whereIn('id', $this->selectedTimecardIds)
            ->get();

        $ability = match ($this->bulkAction) {
            'approve' => 'approve',
            'reject' => 'reject',
            'delete' => 'delete',
            default => null,
        };

        if ($ability === null) {
            return;
        }

        $authorizedTimecards = $timecards->filter(fn (Timecard $timecard): bool => Gate::forUser($actor)->allows($ability, $timecard));

        $result = app(TimecardLifecycleService::class)->applyBulkAction(
            $authorizedTimecards,
            $this->bulkAction,
            $actor,
            $this->bulkRejectionReason,
        );

        $unauthorizedCount = $timecards->count() - $authorizedTimecards->count();
        $skipped = $result['skipped'] + $unauthorizedCount;

        session()->flash(
            'success',
            "Bulk {$this->bulkAction} complete: {$result['processed']} processed, {$skipped} skipped."
        );

        $this->resetBulkSelection();
        $this->bulkAction = '';
        $this->bulkRejectionReason = null;
        $this->resetPage();
    }

    private function resetBulkSelection(): void
    {
        $this->selectedTimecardIds = [];
        $this->selectPage = false;
    }

    private function baseQuery()
    {
        return Timecard::query()
            ->with(['user'])
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->userFilter !== '', fn ($query) => $query->where('user_id', $this->userFilter))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('week_starting', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('week_ending', '<=', $this->dateTo));
    }

    public function render()
    {
        return view('timecards::livewire.admin.timecards.index', [
            'timecards' => $this->baseQuery()
                ->latest('week_starting')
                ->paginate(15),
            'users' => User::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }
}
