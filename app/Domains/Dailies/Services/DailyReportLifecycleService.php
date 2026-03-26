<?php

namespace App\Domains\Dailies\Services;

use App\Core\User\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyReportLifecycleService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createDraftForUser(User $user, array $attributes): DailyReport
    {
        $payload = $this->buildPayload($attributes);
        $payload['user_id'] = $user->id;
        $payload['status'] = DailyReport::STATUS_DRAFT;
        $payload['submitted_by_id'] = null;
        $payload['rejection_reason'] = null;

        return DailyReport::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateEditable(DailyReport $dailyReport, array $attributes): DailyReport
    {
        if (! in_array($dailyReport->status, [DailyReport::STATUS_DRAFT, DailyReport::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages([
                'dailyReport' => 'Only draft or rejected daily reports may be updated.',
            ]);
        }

        $dailyReport->update($this->buildPayload($attributes));

        return $dailyReport->fresh();
    }

    public function submit(DailyReport $dailyReport, User $submitter): DailyReport
    {
        if ($dailyReport->status !== DailyReport::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'dailyReport' => 'Only draft daily reports may be submitted.',
            ]);
        }

        if (filled($dailyReport->project_id) === false && blank($dailyReport->custom_project_name)) {
            throw ValidationException::withMessages([
                'custom_project_name' => 'Select a project or provide a custom project name before submitting.',
            ]);
        }

        $dailyReport->update([
            'status' => DailyReport::STATUS_SUBMITTED,
            'submitted_by_id' => $submitter->id,
            'rejection_reason' => null,
        ]);

        return $dailyReport->fresh();
    }

    public function approve(DailyReport $dailyReport, User $approver): DailyReport
    {
        if ($dailyReport->status !== DailyReport::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'dailyReport' => 'Only submitted daily reports may be approved.',
            ]);
        }

        $dailyReport->update([
            'status' => DailyReport::STATUS_APPROVED,
            'rejection_reason' => null,
        ]);

        return $dailyReport->fresh();
    }

    public function reject(DailyReport $dailyReport, string $reason): DailyReport
    {
        if ($dailyReport->status !== DailyReport::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'dailyReport' => 'Only submitted daily reports may be rejected.',
            ]);
        }

        $dailyReport->update([
            'status' => DailyReport::STATUS_REJECTED,
            'rejection_reason' => trim($reason),
        ]);

        return $dailyReport->fresh();
    }

    public function delete(DailyReport $dailyReport): void
    {
        if ($dailyReport->status === DailyReport::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'dailyReport' => 'Approved daily reports may not be deleted.',
            ]);
        }

        DB::transaction(function () use ($dailyReport): void {
            $dailyReport->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function buildPayload(array $attributes): array
    {
        $projectId = Arr::get($attributes, 'project_id');
        $customProjectName = Arr::get($attributes, 'custom_project_name');
        $workPerformed = $this->normalizeWorkPerformedItems((array) Arr::get($attributes, 'work_performed', []));
        $hourTotals = $this->calculateHourTotalsFromWorkPerformed($workPerformed);

        return [
            'project_id' => filled($projectId) ? (string) $projectId : null,
            'custom_project_name' => filled($customProjectName) ? trim((string) $customProjectName) : null,
            'report_date' => (string) Arr::get($attributes, 'report_date'),
            'weather_condition' => filled(Arr::get($attributes, 'weather_condition')) ? trim((string) Arr::get($attributes, 'weather_condition')) : null,
            'temperature' => filled(Arr::get($attributes, 'temperature')) ? (float) Arr::get($attributes, 'temperature') : null,
            'temperature_unit' => (string) Arr::get($attributes, 'temperature_unit', 'F'),
            'total_regular_hours' => $hourTotals['regular'],
            'total_overtime_hours' => $hourTotals['overtime'],
            'total_hours' => $hourTotals['regular'] + $hourTotals['overtime'],
            'additional_notes' => filled(Arr::get($attributes, 'additional_notes')) ? (string) Arr::get($attributes, 'additional_notes') : null,
            'work_performed' => $workPerformed,
            'materials_used' => $this->filterItems((array) Arr::get($attributes, 'materials_used', [])),
            'equipment_used' => $this->filterItems((array) Arr::get($attributes, 'equipment_used', [])),
            'safety_issues' => $this->filterItems((array) Arr::get($attributes, 'safety_issues', [])),
            'delays' => $this->filterItems((array) Arr::get($attributes, 'delays', [])),
            'visitors' => $this->filterItems((array) Arr::get($attributes, 'visitors', [])),
            'onsite_employees' => $this->filterItems((array) Arr::get($attributes, 'onsite_employees', [])),
        ];
    }

    /**
     * @param  array<int, array{description:string,hours:?float,employees:array<int, string>,is_overtime:bool}>  $items
     * @return array{regular:float,overtime:float}
     */
    private function calculateHourTotalsFromWorkPerformed(array $items): array
    {
        $regularHours = 0.0;
        $overtimeHours = 0.0;

        foreach ($items as $item) {
            $hours = $item['hours'] ?? null;

            if ($hours === null || $hours <= 0) {
                continue;
            }

            if ($item['is_overtime']) {
                $overtimeHours += $hours;
            } else {
                $regularHours += $hours;
            }
        }

        return [
            'regular' => round($regularHours, 2),
            'overtime' => round($overtimeHours, 2),
        ];
    }

    /**
     * @param  array<mixed>  $items
     * @return array<int, string>
     */
    private function filterItems(array $items): array
    {
        return array_values(
            array_filter(
                array_map(fn (mixed $item): string => trim((string) $item), $items),
                fn (string $item): bool => $item !== '',
            )
        );
    }

    /**
     * @param  array<mixed>  $items
     * @return array<int, array{description:string,hours:?float,employees:array<int, string>,is_overtime:bool}>
     */
    private function normalizeWorkPerformedItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $description = trim($item);

                if ($description !== '') {
                    $normalized[] = [
                        'description' => $description,
                        'hours' => null,
                        'employees' => [],
                        'is_overtime' => false,
                    ];
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $description = trim((string) ($item['description'] ?? ''));
            $hours = isset($item['hours']) && is_numeric($item['hours'])
                ? (float) $item['hours']
                : null;
            $employees = $this->filterItems((array) ($item['employees'] ?? []));
            $isOvertime = (bool) ($item['is_overtime'] ?? false);

            if ($description === '') {
                continue;
            }

            $normalized[] = [
                'description' => $description,
                'hours' => $hours,
                'employees' => $employees,
                'is_overtime' => $isOvertime,
            ];
        }

        return array_values($normalized);
    }
}
