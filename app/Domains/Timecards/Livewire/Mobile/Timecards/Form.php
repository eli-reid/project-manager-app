<?php

namespace App\Domains\Timecards\Livewire\Mobile\Timecards;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Livewire\User\Timecards\Form as DesktopForm;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\LeaveBalanceService;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use App\Domains\Timecards\Services\TimecardWeekService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.mobile')]
class Form extends DesktopForm
{
    public function applyStartTimePreset(int $index, string $startTime): void
    {
        if (! isset($this->entries[$index]) || ($this->entries[$index]['delete'] ?? false)) {
            return;
        }

        $allowedPresets = ['06:00', '06:30', '07:00', '07:30', '08:00'];

        if (! in_array($startTime, $allowedPresets, true)) {
            return;
        }

        $this->entries[$index]['start_time'] = $startTime;
        $this->resetValidation('entries.'.$index.'.start_time');
    }

    public function applyHoursPreset(int $index, string $hours): void
    {
        if (! isset($this->entries[$index]) || ($this->entries[$index]['delete'] ?? false)) {
            return;
        }

        $allowedPresets = ['4.00', '6.00', '8.00', '10.00', '12.00'];

        if (! in_array($hours, $allowedPresets, true)) {
            return;
        }

        $this->entries[$index]['hours'] = $hours;
        $this->resetValidation('entries.'.$index.'.hours');
    }

    public function save(): void
    {
        $validated = $this->validate();
        $this->assertValidCostCodeMapping($validated['entries'] ?? []);

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

        $this->redirectRoute('timecards.mobile.show', ['timecard' => $timecard], navigate: true);
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
        $timecardWeekService = app(TimecardWeekService::class);
        $weekStart = $timecardWeekService->normalizeWeekStart($this->week_starting);
        $weekOptions = $user instanceof User
            ? $timecardWeekService->futureWeekOptions((string) $user->id, includePreviousWeek: true)
            : collect();

        if ($weekOptions->doesntContain('start', $weekStart->toDateString())) {
            $weekOptions->prepend([
                'start' => $weekStart->toDateString(),
                'label' => $weekStart->format('M j').' - '.$weekStart->copy()->addDays(6)->format('M j, Y'),
            ]);
        }

        return view('timecards::livewire.mobile.timecards.form', [
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
            'weekOptions' => $weekOptions,
            'weekDays' => collect(range(0, 6))
                ->map(fn (int $offset): Carbon => $weekStart->copy()->addDays($offset)),
        ])->title($this->isEdit ? __('Edit Timecard') : __('Create Timecard'));
    }
}
