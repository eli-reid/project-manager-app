<?php

namespace App\Domains\Timecards\Livewire\User\Timecards;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\LeaveBalanceService;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Timecard Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Timecard $timecard = null;

    public bool $isEdit = false;

    public string $week_starting = '';

    public ?string $notes = null;

    /**
     * @var array<int, array{id:?string,row_key:string,day_of_week:int,start_time:?string,project_id:?string,cost_code_id:?string,custom_project_name:?string,hours:string,notes:?string,delete:bool}>
     */
    public array $entries = [];

    public function mount(?Timecard $timecard = null): void
    {
        $timecardWeekService = app(TimecardWeekService::class);

        if ($timecard !== null && $timecard->exists) {
            $this->authorize('update', $timecard);

            $this->timecard = $timecard;
            $this->isEdit = true;
            $this->week_starting = (string) optional($timecard->week_starting)->toDateString();
            $this->notes = $timecard->notes;
            $this->entries = $timecard->entries()
                ->orderBy('date')
                ->get()
                ->map(fn ($entry): array => [
                    'id' => (string) $entry->id,
                    'row_key' => 'entry-'.$entry->id,
                    'day_of_week' => (int) optional($entry->date)->dayOfWeek,
                    'start_time' => $entry->start_time ? substr((string) $entry->start_time, 0, 5) : null,
                    'project_id' => $entry->project_id ? (string) $entry->project_id : null,
                    'cost_code_id' => $entry->cost_code_id ? (string) $entry->cost_code_id : null,
                    'custom_project_name' => $entry->custom_project_name,
                    'hours' => number_format((float) $entry->hours, 2, '.', ''),
                    'notes' => $entry->notes,
                    'delete' => false,
                ])
                ->all();

            if ($this->entries === []) {
                $this->addEntry();
            }

            return;
        }

        $this->authorize('create', Timecard::class);

        $userId = Auth::id();
        abort_unless(is_string($userId), 401);

        $currentWeekStart = $timecardWeekService->currentWeekStart();
        $defaultWeekStart = $currentWeekStart->copy()->subWeek();

        if ($timecardWeekService->hasExistingTimecardForWeek($userId, $defaultWeekStart)) {
            $defaultWeekStart = $currentWeekStart;
        }

        $requestedWeek = request()->query('week_starting', $defaultWeekStart->toDateString());
        $this->week_starting = $timecardWeekService->normalizeWeekStart((string) $requestedWeek)->toDateString();
        $this->addEntry();
    }

    protected function rules(): array
    {
        return [
            'week_starting' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'entries' => ['array'],
            'entries.*.id' => ['nullable', 'string', 'exists:timecard_entries,id'],
            'entries.*.row_key' => ['required', 'string'],
            'entries.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'entries.*.start_time' => ['nullable', 'date_format:H:i'],
            'entries.*.project_id' => ['nullable', 'exists:projects,id'],
            'entries.*.cost_code_id' => ['nullable', 'exists:cost_codes,id'],
            'entries.*.custom_project_name' => ['nullable', 'string', 'max:255'],
            'entries.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'entries.*.notes' => ['nullable', 'string'],
            'entries.*.delete' => ['boolean'],
        ];
    }

    public function addEntry(): void
    {
        $this->entries[] = $this->newEntry();
    }

    public function addLeaveEntry(string $leaveCategory): void
    {
        if (! in_array($leaveCategory, ['sick', 'vacation'], true)) {
            return;
        }

        $leaveProjectId = Project::query()
            ->where('leave_category', $leaveCategory)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->value('id');

        if (! is_string($leaveProjectId)) {
            $this->addError('entries', 'No active '.str($leaveCategory)->title().' leave project is configured.');

            return;
        }

        $this->entries[] = $this->newEntry($leaveProjectId);
    }

    public function removeEntry(int $index): void
    {
        if (! isset($this->entries[$index])) {
            return;
        }

        if ($this->entries[$index]['id'] !== null) {
            $this->entries[$index]['delete'] = true;

            return;
        }

        unset($this->entries[$index]);
        $this->entries = array_values($this->entries);

        if ($this->entries === []) {
            $this->addEntry();
        }
    }

    public function updatedWeekStarting(string $value): void
    {
        unset($value);
    }

    public function save(): void
    {
        $validated = $this->validate();
        $this->assertValidCostCodeMapping($validated['entries'] ?? []);

        // Convert day_of_week to actual dates
        $entries = $this->convertDayOfWeekToDate($validated['entries'] ?? []);
        $validated['entries'] = $entries;

        $lifecycleService = app(TimecardLifecycleService::class);
        $user = Auth::user();
        abort_unless($user !== null, 401);

        if ($this->isEdit) {
            $timecard = $this->timecard;
            if ($timecard === null) {
                return;
            }

            $this->authorize('update', $timecard);
            $timecard = $lifecycleService->updateDraft($timecard, $validated, $validated['entries'] ?? []);

            session()->flash('success', 'Timecard updated successfully.');
        } else {
            $this->authorize('create', Timecard::class);
            $timecard = $lifecycleService->createDraftForUser($user, $validated['week_starting'], $validated);
            $timecard = $lifecycleService->updateDraft($timecard, $validated, $validated['entries'] ?? []);

            session()->flash('success', 'Timecard created successfully.');
        }

        $this->redirectRoute('timecards.show', ['timecard' => $timecard], navigate: true);
    }

    public function render()
    {
        $projects = Project::query()
            ->where(function ($query): void {
                $query->where('is_active', true)
                    ->orWhereNotNull('leave_category');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'leave_category']);

        $leaveProjectsByCategory = $projects
            ->whereNotNull('leave_category')
            ->keyBy('leave_category');

        $user = Auth::user();

        return view('timecards::livewire.user.timecards.form', [
            'projects' => $projects,
            'leaveProjectsByCategory' => $leaveProjectsByCategory,
            'leaveBalances' => $user instanceof User
                ? app(LeaveBalanceService::class)->forUser($user)
                : ['sick' => ['allowed' => 0.0, 'used' => 0.0, 'remaining' => 0.0], 'vacation' => ['allowed' => 0.0, 'used' => 0.0, 'remaining' => 0.0]],
            'costCodesByProject' => CostCode::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'project_id', 'code', 'description'])
                ->groupBy('project_id'),
        ]);
    }

    /**
     * Convert day_of_week values to actual dates based on week_starting
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    protected function convertDayOfWeekToDate(array $entries): array
    {
        $weekStart = Carbon::parse($this->week_starting);

        return array_map(function (array $entry) use ($weekStart) {
            $dayOffset = ((int) $entry['day_of_week'] - $weekStart->dayOfWeek + 7) % 7;

            $entry['date'] = $weekStart->copy()->addDays($dayOffset)->toDateString();
            unset($entry['day_of_week']);
            unset($entry['row_key']);

            return $entry;
        }, $entries);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    protected function assertValidCostCodeMapping(array $entries): void
    {
        $projectIds = collect($entries)
            ->pluck('project_id')
            ->filter(fn ($projectId) => filled($projectId))
            ->map(fn ($projectId): string => (string) $projectId)
            ->unique()
            ->values()
            ->all();

        $leaveProjectIds = Project::query()
            ->whereIn('id', $projectIds)
            ->whereNotNull('leave_category')
            ->pluck('id')
            ->map(fn ($projectId): string => (string) $projectId)
            ->all();

        foreach ($entries as $index => $entry) {
            $costCodeId = (string) ($entry['cost_code_id'] ?? '');
            $projectId = (string) ($entry['project_id'] ?? '');

            if ($costCodeId !== '' && in_array($projectId, $leaveProjectIds, true)) {
                throw ValidationException::withMessages([
                    "entries.{$index}.cost_code_id" => 'Leave entries cannot use cost codes.',
                ]);
            }

            if ($costCodeId === '') {
                continue;
            }

            if ($projectId === '') {
                throw ValidationException::withMessages([
                    "entries.{$index}.cost_code_id" => 'Select a project before selecting a cost code.',
                ]);
            }

            $isValidForProject = CostCode::query()
                ->whereKey($costCodeId)
                ->where('project_id', $projectId)
                ->exists();

            if (! $isValidForProject) {
                throw ValidationException::withMessages([
                    "entries.{$index}.cost_code_id" => 'Selected cost code does not belong to the selected project.',
                ]);
            }
        }
    }

    protected function newEntry(?string $projectId = null): array
    {
        return [
            'id' => null,
            'row_key' => 'entry-'.(string) Str::ulid(),
            'day_of_week' => app(TimecardWeekService::class)->currentWeekStart()->dayOfWeek,
            'start_time' => null,
            'project_id' => $projectId,
            'cost_code_id' => null,
            'custom_project_name' => null,
            'hours' => '0.00',
            'notes' => null,
            'delete' => false,
        ];
    }
}
