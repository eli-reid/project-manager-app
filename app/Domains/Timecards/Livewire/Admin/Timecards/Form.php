<?php

namespace App\Domains\Timecards\Livewire\Admin\Timecards;

use App\Core\User\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Timecard Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Timecard $timecard = null;

    public bool $isEdit = false;

    public ?string $user_id = null;

    public string $week_starting = '';

    public ?string $notes = null;

    /**
     * @var array<int, array{id:?string,date:string,start_time:?string,project_id:?string,custom_project_name:?string,hours:string,notes:?string,delete:bool}>
     */
    public array $entries = [];

    public function mount(?Timecard $timecard = null): void
    {
        $timecardWeekService = app(TimecardWeekService::class);

        if ($timecard !== null && $timecard->exists) {
            $this->authorize('update', $timecard);

            $this->timecard = $timecard;
            $this->isEdit = true;
            $this->user_id = (string) $timecard->user_id;
            $this->week_starting = (string) optional($timecard->week_starting)->toDateString();
            $this->notes = $timecard->notes;
            $this->entries = $timecard->entries()
                ->orderBy('date')
                ->get()
                ->map(fn ($entry): array => [
                    'id' => (string) $entry->id,
                    'date' => (string) optional($entry->date)->toDateString(),
                    'start_time' => $entry->start_time ? substr((string) $entry->start_time, 0, 5) : null,
                    'project_id' => $entry->project_id ? (string) $entry->project_id : null,
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
        $requestedWeek = request()->query('week_starting', $timecardWeekService->currentWeekStart()->toDateString());
        $this->user_id = request()->query('user_id');
        $this->week_starting = $timecardWeekService->normalizeWeekStart((string) $requestedWeek)->toDateString();
        $this->addEntry();
    }

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'week_starting' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'entries' => ['array'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.start_time' => ['nullable', 'date_format:H:i'],
            'entries.*.project_id' => ['nullable', 'exists:projects,id'],
            'entries.*.custom_project_name' => ['nullable', 'string', 'max:255'],
            'entries.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'entries.*.notes' => ['nullable', 'string'],
            'entries.*.delete' => ['boolean'],
        ];
    }

    public function addEntry(): void
    {
        $this->entries[] = [
            'id' => null,
            'date' => $this->week_starting,
            'start_time' => null,
            'project_id' => null,
            'custom_project_name' => null,
            'hours' => '0.00',
            'notes' => null,
            'delete' => false,
        ];
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
        foreach ($this->entries as $index => $entry) {
            if (($entry['id'] ?? null) === null && (($entry['date'] ?? '') === '' || ($entry['date'] ?? '') === $this->week_starting)) {
                $this->entries[$index]['date'] = $value;
            }
        }
    }

    public function save(): void
    {
        $validated = $this->validate();
        $user = User::query()->findOrFail($validated['user_id']);
        $lifecycleService = app(TimecardLifecycleService::class);

        if ($this->isEdit) {
            $timecard = $this->timecard;
            if ($timecard === null) {
                return;
            }

            $this->authorize('update', $timecard);
            $timecard = $lifecycleService->updateForAdmin($timecard, $user, $validated, $validated['entries'] ?? []);

            session()->flash('success', 'Timecard updated successfully.');
        } else {
            $this->authorize('create', Timecard::class);
            $timecard = $lifecycleService->createForUser($user, $validated['week_starting'], $validated, $validated['entries'] ?? []);

            session()->flash('success', 'Timecard created successfully.');
        }

        $this->redirectRoute('admin.timecards.show', ['timecard' => $timecard], navigate: true);
    }

    public function render()
    {
        return view('timecards::livewire.admin.timecards.form', [
            'users' => User::query()->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
