<?php

namespace App\Domains\Dailies\Livewire\User\Dailies;

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Core\WeatherApi\Contracts\WeatherApiContract;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Dailies\Services\DailyReportLifecycleService;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daily Report Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?DailyReport $dailyReport = null;

    public bool $isEdit = false;

    public ?string $project_id = null;

    public ?string $custom_project_name = null;

    public string $report_date = '';

    public ?string $weather_condition = null;

    public ?string $temperature = null;

    public string $temperature_unit = 'F';

    public string $total_regular_hours = '0.00';

    public string $total_overtime_hours = '0.00';

    public ?string $additional_notes = null;

    /** @var array<int, array{description:string,hours:?string,employees:array<int, string>,is_overtime:bool}> */
    public array $work_performed = [];

    /** @var array<int, string> */
    public array $materials_used = [];

    /** @var array<int, string> */
    public array $equipment_used = [];

    /** @var array<int, string> */
    public array $safety_issues = [];

    /** @var array<int, string> */
    public array $delays = [];

    /** @var array<int, string> */
    public array $visitors = [];

    /** @var array<int, string> */
    public array $onsite_employees = [];

    public ?string $weather_source_location = null;

    private function isMobileRoute(): bool
    {
        return request()->routeIs('dailies.mobile.*');
    }

    private function routeNameForShow(): string
    {
        return $this->isMobileRoute() ? 'dailies.mobile.show' : 'dailies.show';
    }

    private function routeNameForIndex(): string
    {
        return $this->isMobileRoute() ? 'dailies.mobile.index' : 'dailies.index';
    }

    public function mount(?DailyReport $dailyReport = null): void
    {
        if ($dailyReport !== null && $dailyReport->exists) {
            $this->authorize('update', $dailyReport);
            $this->dailyReport = $dailyReport;
            $this->isEdit = true;
            $this->project_id = $dailyReport->project_id;
            $this->custom_project_name = $dailyReport->custom_project_name;
            $this->report_date = (string) optional($dailyReport->report_date)->toDateString();
            $this->weather_condition = $dailyReport->weather_condition;
            $this->temperature = $dailyReport->temperature !== null ? number_format((float) $dailyReport->temperature, 2, '.', '') : null;
            $this->temperature_unit = $dailyReport->temperature_unit ?? 'F';
            $this->total_regular_hours = number_format((float) $dailyReport->total_regular_hours, 2, '.', '');
            $this->total_overtime_hours = number_format((float) $dailyReport->total_overtime_hours, 2, '.', '');
            $this->additional_notes = $dailyReport->additional_notes;
            $this->work_performed = $this->normalizeWorkPerformedForForm($dailyReport->work_performed ?? []);
            $this->syncHourTotalsFromWorkPerformed();
            $this->materials_used = $dailyReport->materials_used ?? [];
            $this->equipment_used = $dailyReport->equipment_used ?? [];
            $this->safety_issues = $dailyReport->safety_issues ?? [];
            $this->delays = $dailyReport->delays ?? [];
            $this->visitors = $dailyReport->visitors ?? [];
            $this->onsite_employees = $dailyReport->onsite_employees ?? [];

            return;
        }

        $this->authorize('create', DailyReport::class);
        $this->report_date = now()->toDateString();
        $this->work_performed = [['description' => '', 'hours' => null, 'employees' => [], 'is_overtime' => false]];
        $this->syncHourTotalsFromWorkPerformed();
    }

    public function addWorkPerformedItem(): void
    {
        $this->work_performed[] = ['description' => '', 'hours' => null, 'employees' => [], 'is_overtime' => false];
    }

    public function removeWorkPerformedItem(int $index): void
    {
        if (! isset($this->work_performed[$index])) {
            return;
        }

        unset($this->work_performed[$index]);
        $this->work_performed = array_values($this->work_performed);

        if ($this->work_performed === []) {
            $this->addWorkPerformedItem();
        }

        $this->syncHourTotalsFromWorkPerformed();
    }

    public function updatedWorkPerformed(): void
    {
        $this->syncHourTotalsFromWorkPerformed();
    }

    public function addItem(string $field): void
    {
        $allowed = ['materials_used', 'equipment_used', 'safety_issues', 'delays', 'visitors'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        $this->$field[] = '';
    }

    public function removeItem(string $field, int $index): void
    {
        $allowed = ['materials_used', 'equipment_used', 'safety_issues', 'delays', 'visitors'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        $items = $this->$field;
        unset($items[$index]);
        $this->$field = array_values($items);
    }

    protected function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'custom_project_name' => ['nullable', 'required_without:project_id', 'string', 'max:255'],
            'report_date' => ['required', 'date'],
            'total_regular_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'total_overtime_hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'additional_notes' => ['nullable', 'string'],
            'work_performed' => ['nullable', 'array'],
            'work_performed.*.description' => ['nullable', 'string', 'max:500'],
            'work_performed.*.hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'work_performed.*.employees' => ['nullable', 'array'],
            'work_performed.*.employees.*' => ['string', 'max:255'],
            'work_performed.*.is_overtime' => ['boolean'],
            'materials_used' => ['nullable', 'array'],
            'materials_used.*' => ['string', 'max:500'],
            'equipment_used' => ['nullable', 'array'],
            'equipment_used.*' => ['string', 'max:500'],
            'safety_issues' => ['nullable', 'array'],
            'safety_issues.*' => ['string', 'max:500'],
            'delays' => ['nullable', 'array'],
            'delays.*' => ['string', 'max:500'],
            'visitors' => ['nullable', 'array'],
            'visitors.*' => ['string', 'max:500'],
            'onsite_employees' => ['nullable', 'array'],
            'onsite_employees.*' => ['string', 'max:255'],
        ];
    }

    public function updatedProjectId(?string $value): void
    {
        unset($value);
        $this->hydrateWeatherFromApi();
    }

    private function hydrateWeatherFromApi(): void
    {
        $weatherApi = app(WeatherApiContract::class);
        $location = $this->resolveWeatherLocation();

        $payload = $location !== null
            ? $weatherApi->getCurrentWeather($location)
            : $weatherApi->getWeatherByIp((string) request()->ip());

        if ($payload === null) {
            return;
        }

        $extracted = $weatherApi->extractWeatherForDailyReport($payload);

        $this->weather_condition = filled($extracted['condition'] ?? null) ? (string) $extracted['condition'] : $this->weather_condition;
        $this->temperature = isset($extracted['temperature']) ? (string) $extracted['temperature'] : $this->temperature;
        $this->temperature_unit = (string) ($extracted['temperature_unit'] ?? 'F');
        $this->weather_source_location = filled($extracted['location_name'] ?? null)
            ? (string) $extracted['location_name']
            : ($location ?? 'IP location');
    }

    public function saveAsDraft(): void
    {
        $this->syncHourTotalsFromWorkPerformed();
        $this->hydrateWeatherFromApi();

        $validated = $this->validate();
        $validated['weather_condition'] = $this->weather_condition;
        $validated['temperature'] = $this->temperature;
        $validated['temperature_unit'] = $this->temperature_unit;

        $lifecycleService = app(DailyReportLifecycleService::class);
        $user = Auth::user();
        abort_unless($user !== null, 401);

        if ($this->isEdit) {
            $dailyReport = $this->dailyReport;

            if ($dailyReport === null) {
                return;
            }

            $this->authorize('update', $dailyReport);

            try {
                $dailyReport = $lifecycleService->updateEditable($dailyReport, $validated);
            } catch (ValidationException $exception) {
                $this->setValidationErrors($exception);

                return;
            }

            session()->flash('success', 'Daily report updated successfully.');
            $this->redirectRoute($this->routeNameForShow(), ['dailyReport' => $dailyReport], navigate: true);

            return;
        }

        $this->authorize('create', DailyReport::class);
        $dailyReport = $lifecycleService->createDraftForUser($user, $validated);

        session()->flash('success', 'Daily report draft created successfully.');

        $this->redirectRoute($this->routeNameForShow(), ['dailyReport' => $dailyReport], navigate: true);
    }

    public function saveAndSubmit(): void
    {
        $this->syncHourTotalsFromWorkPerformed();
        $this->hydrateWeatherFromApi();

        $validated = $this->validate();
        $validated['weather_condition'] = $this->weather_condition;
        $validated['temperature'] = $this->temperature;
        $validated['temperature_unit'] = $this->temperature_unit;

        $lifecycleService = app(DailyReportLifecycleService::class);
        $user = Auth::user();
        abort_unless($user !== null, 401);

        if ($this->isEdit) {
            $dailyReport = $this->dailyReport;

            if ($dailyReport === null) {
                return;
            }

            $this->authorize('update', $dailyReport);

            try {
                $dailyReport = $lifecycleService->updateEditable($dailyReport, $validated);
            } catch (ValidationException $exception) {
                $this->setValidationErrors($exception);

                return;
            }
        } else {
            $this->authorize('create', DailyReport::class);
            $dailyReport = $lifecycleService->createDraftForUser($user, $validated);
            $this->dailyReport = $dailyReport;
            $this->isEdit = true;
        }

        $this->authorize('submit', $dailyReport);

        try {
            $dailyReport = $lifecycleService->submit($dailyReport, $user);
        } catch (ValidationException $exception) {
            $this->setValidationErrors($exception);

            return;
        }

        session()->flash('success', 'Daily report submitted successfully.');
        $this->redirectRoute($this->routeNameForShow(), ['dailyReport' => $dailyReport], navigate: true);
    }

    public function delete(): void
    {
        $dailyReport = $this->dailyReport;

        if ($dailyReport === null) {
            return;
        }

        $this->authorize('delete', $dailyReport);

        try {
            app(DailyReportLifecycleService::class)->delete($dailyReport);
        } catch (ValidationException $exception) {
            $this->setValidationErrors($exception);

            return;
        }

        session()->flash('success', 'Daily report deleted successfully.');
        $this->redirectRoute($this->routeNameForIndex(), navigate: true);
    }

    private function setValidationErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }
    }

    public function render()
    {
        $user = Auth::user();

        $view = $this->isMobileRoute()
            ? 'dailies::livewire.mobile.dailies.form'
            : 'dailies::livewire.user.dailies.form';

        return view($view, [
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'employees' => User::query()
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['first_name', 'last_name'])
                ->map(fn (User $employee): string => trim($employee->first_name.' '.$employee->last_name))
                ->filter(fn (string $name): bool => $name !== '')
                ->values(),
            'canSubmit' => $this->dailyReport !== null
                ? $user?->can('submit', $this->dailyReport) === true
                : $user?->hasPermission('dailies.submit') === true,
            'canDelete' => $this->dailyReport !== null && $user?->can('delete', $this->dailyReport) === true,
        ])->layout($this->isMobileRoute() ? 'layouts.mobile' : 'layouts.app');
    }

    /**
     * @param  array<mixed>  $items
     * @return array<int, array{description:string,hours:?string,employees:array<int, string>,is_overtime:bool}>
     */
    private function normalizeWorkPerformedForForm(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $description = trim($item);

                if ($description !== '') {
                    $normalized[] = ['description' => $description, 'hours' => null, 'employees' => [], 'is_overtime' => false];
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $description = trim((string) Arr::get($item, 'description', ''));

            if ($description === '') {
                continue;
            }

            $hours = Arr::get($item, 'hours');
            $employeeItems = Arr::get($item, 'employees', []);

            if (! is_array($employeeItems)) {
                $employeeItems = [];
            }

            $employees = array_values(
                array_filter(
                    array_map(fn (mixed $employee): string => trim((string) $employee), $employeeItems),
                    fn (string $employee): bool => $employee !== '',
                )
            );

            $normalized[] = [
                'description' => $description,
                'hours' => is_numeric($hours) ? number_format((float) $hours, 2, '.', '') : null,
                'employees' => $employees,
                'is_overtime' => (bool) Arr::get($item, 'is_overtime', false),
            ];
        }

        if ($normalized === []) {
            return [['description' => '', 'hours' => null, 'employees' => [], 'is_overtime' => false]];
        }

        return $normalized;
    }

    private function syncHourTotalsFromWorkPerformed(): void
    {
        $regularHours = 0.0;
        $overtimeHours = 0.0;

        foreach ($this->work_performed as $item) {
            $hours = Arr::get($item, 'hours');

            if (! is_numeric($hours)) {
                continue;
            }

            $numericHours = (float) $hours;

            if ($numericHours <= 0) {
                continue;
            }

            if ((bool) Arr::get($item, 'is_overtime', false)) {
                $overtimeHours += $numericHours;
            } else {
                $regularHours += $numericHours;
            }
        }

        $this->total_regular_hours = number_format($regularHours, 2, '.', '');
        $this->total_overtime_hours = number_format($overtimeHours, 2, '.', '');
    }

    private function resolveWeatherLocation(): ?string
    {
        $defaultLocation = trim(Settings::get('weatherapi.default_location', '')->toString());

        if (blank($this->project_id)) {
            return $defaultLocation !== '' ? $defaultLocation : null;
        }

        $project = Project::query()->with('address')->find($this->project_id);

        if ($project === null || $project->address === null) {
            return $defaultLocation !== '' ? $defaultLocation : null;
        }

        $address = trim(implode(', ', array_filter([
            $project->address->address1,
            $project->address->city,
            $project->address->state,
            $project->address->zip,
        ], fn (?string $value): bool => filled($value))));

        if ($address !== '') {
            return $address;
        }

        return $defaultLocation !== '' ? $defaultLocation : null;
    }
}
