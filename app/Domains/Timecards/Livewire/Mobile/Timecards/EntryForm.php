<?php

namespace App\Domains\Timecards\Livewire\Mobile\Timecards;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use App\Domains\Timecards\Services\LeaveBalanceService;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.mobile')]
class EntryForm extends Component
{
    use AuthorizesRequests;

    /** @var array<int, string> */
    public const LEAVE_TYPES = ['sick', 'vacation'];

    /** @var array<int, string> */
    public const HOUR_PRESETS = ['4.00', '6.00', '8.00', '10.00'];

    /** @var array<int, string> */
    public const START_TIME_PRESETS = ['06:00', '06:30', '07:00', '07:30'];

    public Timecard $timecard;

    public ?TimecardEntry $entry = null;

    public string $entryType = 'work';

    public string $date = '';

    public ?string $project_id = null;

    public ?string $cost_code_id = null;

    public ?string $custom_project_name = null;

    public ?string $start_time = null;

    public string $hours = '';

    public ?string $notes = null;

    public ?string $savedMessage = null;

    public function mount(Timecard $timecard, ?TimecardEntry $entry = null): void
    {
        $this->authorize('update', $timecard);
        abort_unless($timecard->status === Timecard::STATUS_DRAFT, 403);

        $this->timecard = $timecard;

        if ($entry !== null && $entry->exists) {
            abort_unless($entry->timecard_id === $timecard->id, 404);

            $this->entry = $entry;
            $this->date = (string) $entry->date?->toDateString();
            $this->project_id = $entry->project_id ? (string) $entry->project_id : null;
            $this->cost_code_id = $entry->cost_code_id ? (string) $entry->cost_code_id : null;
            $this->custom_project_name = $entry->custom_project_name;
            $this->start_time = $entry->start_time ? substr((string) $entry->start_time, 0, 5) : null;
            $this->hours = number_format((float) $entry->hours, 2, '.', '');
            $this->notes = $entry->notes;
            $this->entryType = $this->leaveCategoryFor($this->project_id) ?? 'work';

            return;
        }

        $this->date = $this->defaultDate(request()->query('date'));
        $this->prefillFromLatestWorkEntry();
    }

    protected function rules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$this->timecard->week_starting->toDateString(),
                'before_or_equal:'.$this->timecard->week_ending->toDateString(),
            ],
            'project_id' => ['nullable', 'string', 'exists:projects,id'],
            'cost_code_id' => [
                'nullable',
                'string',
                Rule::prohibitedIf(blank($this->project_id) || $this->entryType !== 'work'),
                Rule::exists('cost_codes', 'id')->where('project_id', $this->project_id),
            ],
            'custom_project_name' => [Rule::requiredIf(blank($this->project_id)), 'nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'hours' => ['required', 'numeric', 'gt:0', 'max:24'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'custom_project_name.required' => __('Pick a project or enter a custom project name.'),
            'cost_code_id.exists' => __('Selected cost code does not belong to the selected project.'),
            'cost_code_id.prohibited' => __('Cost codes are only available for project work.'),
            'date.after_or_equal' => __('Pick a day within this timecard week.'),
            'date.before_or_equal' => __('Pick a day within this timecard week.'),
            'hours.gt' => __('Hours must be greater than zero.'),
        ];
    }

    public function setEntryType(string $type): void
    {
        if ($type === $this->entryType) {
            return;
        }

        if ($type === 'work') {
            $this->entryType = 'work';
            $this->project_id = null;
            $this->cost_code_id = null;
            $this->resetValidation(['project_id', 'cost_code_id']);

            return;
        }

        if (! in_array($type, self::LEAVE_TYPES, true)) {
            return;
        }

        $leaveProject = $this->leaveProjects->get($type);

        if ($leaveProject === null) {
            $this->addError('project_id', __('No :type leave project is configured.', ['type' => $type]));

            return;
        }

        $this->entryType = $type;
        $this->project_id = (string) $leaveProject->id;
        $this->cost_code_id = null;
        $this->custom_project_name = null;
        $this->resetValidation(['project_id', 'cost_code_id', 'custom_project_name']);
    }

    public function updatedProjectId(?string $value): void
    {
        $this->cost_code_id = null;

        if (filled($value)) {
            $this->custom_project_name = null;
            $this->resetValidation('custom_project_name');
        }
    }

    public function save(): void
    {
        $this->persist();

        session()->flash('success', __('Entry saved.'));
        $this->redirectRoute('timecards.mobile.show', ['timecard' => $this->timecard], navigate: true);
    }

    /**
     * Save the entry and reset the form for the next day, keeping project and start time.
     */
    public function saveAndAddAnother(): void
    {
        $savedEntry = $this->persist();

        $nextDate = $savedEntry->date->copy()->addDay();

        $this->entry = null;
        $this->date = $nextDate->lte($this->timecard->week_ending)
            ? $nextDate->toDateString()
            : $savedEntry->date->toDateString();
        $this->hours = '';
        $this->notes = null;
        $this->savedMessage = __('Saved :hours h on :day.', [
            'hours' => number_format((float) $savedEntry->hours, 2),
            'day' => $savedEntry->date->format('D, M j'),
        ]);

        unset($this->dayTotals);
    }

    public function delete(): void
    {
        $this->authorize('update', $this->timecard);

        if ($this->entry === null) {
            return;
        }

        app(TimecardLifecycleService::class)->deleteEntry($this->timecard, $this->entry);

        session()->flash('success', __('Entry deleted.'));
        $this->redirectRoute('timecards.mobile.show', ['timecard' => $this->timecard], navigate: true);
    }

    /**
     * @return EloquentCollection<string, Project>
     */
    #[Computed]
    public function leaveProjects(): EloquentCollection
    {
        return Project::query()
            ->whereIn('leave_category', self::LEAVE_TYPES)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'leave_category'])
            ->unique('leave_category')
            ->keyBy('leave_category');
    }

    /**
     * @return Collection<string, float>
     */
    #[Computed]
    public function dayTotals(): Collection
    {
        return $this->timecard->entries()
            ->when($this->entry !== null, fn ($query) => $query->whereKeyNot($this->entry->id))
            ->get(['date', 'hours'])
            ->groupBy(fn (TimecardEntry $entry): string => $entry->date->toDateString())
            ->map(fn (Collection $entries): float => (float) $entries->sum('hours'));
    }

    public function render()
    {
        $user = Auth::user();

        return view('timecards::livewire.mobile.timecards.entry-form', [
            'projects' => Project::query()
                ->whereNull('leave_category')
                ->where(function ($query): void {
                    $query->where('is_active', true)
                        ->when($this->project_id, fn ($query) => $query->orWhere('id', $this->project_id));
                })
                ->orderBy('name')
                ->get(['id', 'name']),
            'costCodes' => $this->entryType === 'work' && filled($this->project_id)
                ? CostCode::query()
                    ->where('project_id', $this->project_id)
                    ->where(function ($query): void {
                        $query->where('is_active', true)
                            ->when($this->cost_code_id, fn ($query) => $query->orWhere('id', $this->cost_code_id));
                    })
                    ->orderBy('code')
                    ->get(['id', 'code', 'description'])
                : collect(),
            'weekDays' => collect(range(0, 6))
                ->map(fn (int $offset): CarbonInterface => $this->timecard->week_starting->copy()->addDays($offset)),
            'leaveBalances' => $user instanceof User
                ? app(LeaveBalanceService::class)->forUser($user)
                : [],
        ])->title($this->entry ? __('Edit Entry') : __('Add Entry'));
    }

    private function persist(): TimecardEntry
    {
        $this->authorize('update', $this->timecard);

        if (filled($this->project_id)) {
            $this->custom_project_name = null;
        }

        $validated = $this->validate();

        return app(TimecardLifecycleService::class)->saveEntry($this->timecard, $validated, $this->entry);
    }

    private function defaultDate(mixed $requestedDate): string
    {
        $weekStart = $this->timecard->week_starting->copy()->startOfDay();
        $weekEnd = $this->timecard->week_ending->copy()->startOfDay();

        if (is_string($requestedDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) === 1) {
            $candidate = Carbon::parse($requestedDate)->startOfDay();

            if ($candidate->betweenIncluded($weekStart, $weekEnd)) {
                return $candidate->toDateString();
            }
        }

        $today = now()->startOfDay();

        return $today->betweenIncluded($weekStart, $weekEnd)
            ? $today->toDateString()
            : $weekStart->toDateString();
    }

    private function prefillFromLatestWorkEntry(): void
    {
        $latestEntry = $this->timecard->entries()
            ->where(function ($query): void {
                $query->whereNull('project_id')
                    ->orWhereHas('project', fn ($query) => $query->whereNull('leave_category'));
            })
            ->latest('created_at')
            ->latest('id')
            ->first();

        if ($latestEntry === null) {
            return;
        }

        $this->project_id = $latestEntry->project_id ? (string) $latestEntry->project_id : null;
        $this->cost_code_id = $latestEntry->cost_code_id ? (string) $latestEntry->cost_code_id : null;
        $this->custom_project_name = $latestEntry->custom_project_name;
        $this->start_time = $latestEntry->start_time ? substr((string) $latestEntry->start_time, 0, 5) : null;
    }

    private function leaveCategoryFor(?string $projectId): ?string
    {
        if (blank($projectId)) {
            return null;
        }

        $category = Project::query()->whereKey($projectId)->value('leave_category');

        return in_array($category, self::LEAVE_TYPES, true) ? $category : null;
    }
}
