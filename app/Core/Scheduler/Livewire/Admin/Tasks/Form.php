<?php

namespace App\Core\Scheduler\Livewire\Admin\Tasks;

use App\Core\Scheduler\Models\AvailableTask;
use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\Scheduler\Services\ScheduledTaskService;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;


#[Layout('layouts.admin')]
#[Title('Scheduler Task Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?ScheduledTask $scheduledTask = null;

    public bool $isEdit = false;

    public string $name = '';

    public string $available_task_id = '';

    public string $description = '';

    public string $schedule_type = 'daily';

    public string $time = '09:00';

    public string $timezone = 'America/New_York';

    /**
     * @var array<int, int>
     */
    public array $days_of_week = [];

    public ?int $day_of_month = null;

    public ?int $month = null;

    public ?string $specific_date = null;

    public string $repeat_frequency = 'once';

    public int $repeat_interval = 1;

    public ?string $repeat_until = null;

    public ?int $max_occurrences = null;

    public bool $is_active = true;

    public bool $is_enabled = true;

    public int $timecard_days_after_week_end = 0;

    public string $timecard_statuses = 'draft,rejected';

    public bool $timecard_ignore_daily_reminder_limit = false;

    public string $selected_feature_type = '';

    public function mount(?ScheduledTask $scheduledTask = null): void
    {
        if ($scheduledTask !== null && $scheduledTask->exists) {
            $this->authorize('update', $scheduledTask);

            $this->scheduledTask = $scheduledTask;
            $this->isEdit = true;
            $this->name = $scheduledTask->name;
            $this->available_task_id = (string) $scheduledTask->available_task_id;
            $this->description = (string) $scheduledTask->description;
            $this->schedule_type = $scheduledTask->schedule_type;
            $this->time = substr((string) $scheduledTask->time, 0, 5);
            $this->timezone = (string) $scheduledTask->timezone;
            $this->days_of_week = array_map('intval', (array) $scheduledTask->days_of_week);
            $this->day_of_month = $scheduledTask->day_of_month;
            $this->month = $scheduledTask->month;
            $this->specific_date = $scheduledTask->specific_date?->toDateString();
            $this->repeat_frequency = (string) $scheduledTask->repeat_frequency;
            $this->repeat_interval = (int) $scheduledTask->repeat_interval;
            $this->repeat_until = $scheduledTask->repeat_until?->toDateString();
            $this->max_occurrences = $scheduledTask->max_occurrences;
            $this->is_active = (bool) $scheduledTask->is_active;
            $this->is_enabled = (bool) $scheduledTask->is_enabled;
            $this->syncSelectedFeatureType();
            $this->hydrateTaskConfigFields(is_array($scheduledTask->task_config) ? $scheduledTask->task_config : []);

            return;
        }

        $this->authorize('create', ScheduledTask::class);

        $this->resetCreateDefaults();

        $this->available_task_id = (string) AvailableTask::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id');

        $this->syncSelectedFeatureType();

        $defaultTaskConfig = AvailableTask::query()
            ->whereKey($this->available_task_id)
            ->value('task_config');

        $this->hydrateTaskConfigFields(is_array($defaultTaskConfig) ? $defaultTaskConfig : []);
    }

    private function resetCreateDefaults(): void
    {
        $this->name = '';
        $this->description = '';
        $this->schedule_type = 'daily';
        $this->time = '09:00';
        $this->timezone = 'America/New_York';
        $this->days_of_week = [];
        $this->day_of_month = null;
        $this->month = null;
        $this->specific_date = null;
        $this->repeat_frequency = 'once';
        $this->repeat_interval = 1;
        $this->repeat_until = null;
        $this->max_occurrences = null;
        $this->is_active = true;
        $this->is_enabled = true;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'available_task_id' => ['required', 'string', Rule::exists('available_tasks', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'schedule_type' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly', 'specific_date'])],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'days_of_week' => ['nullable', 'array', Rule::requiredIf($this->schedule_type === 'weekly')],
            'days_of_week.*' => ['integer', 'between:0,6'],
            'day_of_month' => ['nullable', 'integer', 'between:1,31', Rule::requiredIf(in_array($this->schedule_type, ['monthly', 'yearly'], true))],
            'month' => ['nullable', 'integer', 'between:1,12', Rule::requiredIf($this->schedule_type === 'yearly')],
            'specific_date' => ['nullable', 'date', Rule::requiredIf($this->schedule_type === 'specific_date')],
            'repeat_frequency' => ['required', Rule::in(['once', 'daily', 'weekly', 'monthly', 'yearly'])],
            'repeat_interval' => ['required', 'integer', 'min:1'],
            'repeat_until' => ['nullable', 'date'],
            'max_occurrences' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_enabled' => ['boolean'],
            'timecard_days_after_week_end' => ['integer', 'min:0', 'max:30', Rule::requiredIf($this->selected_feature_type === 'timecard_reminders')],
            'timecard_statuses' => ['string', Rule::requiredIf($this->selected_feature_type === 'timecard_reminders')],
            'timecard_ignore_daily_reminder_limit' => ['boolean'],
        ];
    }

    public function updatedAvailableTaskId(string $availableTaskId): void
    {
        $this->syncSelectedFeatureType();

        if ($this->isEdit) {
            return;
        }

        $defaultTaskConfig = AvailableTask::query()->whereKey($availableTaskId)->value('task_config');
        $this->hydrateTaskConfigFields(is_array($defaultTaskConfig) ? $defaultTaskConfig : []);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $payload = [
            'name' => $validated['name'],
            'available_task_id' => $validated['available_task_id'],
            'description' => $validated['description'] ?: null,
            'schedule_type' => $validated['schedule_type'],
            'time' => $validated['time'].':00',
            'timezone' => $validated['timezone'],
            'days_of_week' => $validated['schedule_type'] === 'weekly' ? array_values(array_unique(array_map('intval', $validated['days_of_week'] ?? []))) : null,
            'day_of_month' => $validated['day_of_month'],
            'month' => $validated['month'],
            'specific_date' => $validated['specific_date'],
            'repeat_frequency' => $validated['repeat_frequency'],
            'repeat_interval' => $validated['repeat_interval'],
            'repeat_until' => $validated['repeat_until'],
            'max_occurrences' => $validated['max_occurrences'],
            'is_active' => (bool) $validated['is_active'],
            'is_enabled' => (bool) $validated['is_enabled'],
        ];

        if ($this->isEdit) {
            $task = $this->scheduledTask;
            if ($task === null) {
                return;
            }

            $this->authorize('update', $task);

            $payload['task_config'] = $this->buildTaskConfig(is_array($task->task_config) ? $task->task_config : []);
            $payload['updated_by'] = Auth::id();
            $task->update($payload);
        } else {
            $this->authorize('create', ScheduledTask::class);

            $availableTask = AvailableTask::query()->find($validated['available_task_id']);
            $payload['task_config'] = $this->buildTaskConfig(is_array($availableTask?->task_config) ? $availableTask->task_config : []);

            $payload['created_by'] = Auth::id();
            $payload['updated_by'] = Auth::id();
            $task = ScheduledTask::query()->create($payload);
            $this->scheduledTask = $task;
        }

        $task->update([
            'next_run_at' => app(ScheduledTaskService::class)->calculateNextRun($task),
        ]);

        session()->flash('success', $this->isEdit ? 'Scheduled task updated.' : 'Scheduled task created.');

        $this->redirectRoute('admin.scheduler.tasks.index', navigate: true);
    }

    public function render()
    {
        return view('scheduler::livewire.admin.tasks.form', [
            'availableTasks' => AvailableTask::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'feature_type']),
            'timezones' => [
                'America/New_York',
                'America/Chicago',
                'America/Denver',
                'America/Los_Angeles',
                'UTC',
            ],
        ])->title($this->isEdit ? 'Edit Scheduler Task' : 'Create Scheduler Task');
    }

    private function syncSelectedFeatureType(): void
    {
        $this->selected_feature_type = (string) (AvailableTask::query()->whereKey($this->available_task_id)->value('feature_type') ?? '');
    }

    /**
     * @param  array<string, mixed>  $taskConfig
     */
    private function hydrateTaskConfigFields(array $taskConfig): void
    {
        $this->timecard_days_after_week_end = max(0, (int) ($taskConfig['days_after_week_end'] ?? 0));
        $this->timecard_statuses = implode(',', $this->normalizeTimecardStatuses($taskConfig['statuses'] ?? [
            Timecard::STATUS_DRAFT,
            Timecard::STATUS_REJECTED,
        ]));
        $this->timecard_ignore_daily_reminder_limit = (bool) ($taskConfig['ignore_daily_reminder_limit'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $taskConfig
     * @return array<string, mixed>
     */
    private function buildTaskConfig(array $taskConfig): array
    {
        if ($this->selected_feature_type !== 'timecard_reminders') {
            return $taskConfig;
        }

        $taskConfig['days_after_week_end'] = max(0, $this->timecard_days_after_week_end);
        $taskConfig['statuses'] = $this->normalizeTimecardStatuses($this->timecard_statuses);
        $taskConfig['ignore_daily_reminder_limit'] = $this->timecard_ignore_daily_reminder_limit;

        return $taskConfig;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeTimecardStatuses(mixed $statuses): array
    {
        $allowedStatuses = [
            Timecard::STATUS_DRAFT,
            Timecard::STATUS_REJECTED,
            Timecard::STATUS_SUBMITTED,
            Timecard::STATUS_APPROVED,
        ];

        $rawStatuses = is_array($statuses)
            ? $statuses
            : explode(',', (string) $statuses);

        $normalized = collect($rawStatuses)
            ->map(fn (mixed $status): string => strtolower(trim((string) $status)))
            ->filter(fn (string $status): bool => in_array($status, $allowedStatuses, true))
            ->unique()
            ->values()
            ->all();

        if ($normalized === []) {
            return [Timecard::STATUS_DRAFT, Timecard::STATUS_REJECTED];
        }

        return $normalized;
    }
}
