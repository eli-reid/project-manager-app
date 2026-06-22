<?php

namespace App\Domains\Reports\Livewire\User\LaborCostAnalysis;

use App\Domains\Projects\Services\ProjectReportingService;
use App\Domains\Timecards\Services\TimecardReportingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Labor Cost Analysis')]
class Index extends Component
{
    use AuthorizesRequests;

    public string $projectId = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $drillDown = 'project'; // 'project' | 'week' | 'employee'

    public function mount(): void
    {
        $this->authorize('reports.financial.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updatedProjectId(): void
    {
        // Trigger re-render
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('reports.financial.export');

        $rows = $this->buildRows();
        [$fromDate, $toDate] = $this->normalizedDateRange();

        $fileName = 'labor-cost-analysis-'.Str::slug($fromDate.'-to-'.$toDate).'.csv';

        return response()->streamDownload(function () use ($rows, $fromDate, $toDate): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Labor Cost Analysis']);
            fputcsv($handle, ['From', $fromDate ?? '']);
            fputcsv($handle, ['To', $toDate ?? '']);
            fputcsv($handle, []);
            fputcsv($handle, ['Dimension', 'Hours', 'Estimated Labor Cost']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['label'],
                    number_format($row['hours'], 2, '.', ''),
                    number_format($row['cost'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        $projects = app(ProjectReportingService::class)->activeProjects();

        return view('reports::livewire.user.labor-cost-analysis.index', [
            'rows' => $this->buildRows(),
            'projects' => $projects,
        ]);
    }

    /**
     * @return array<int, array{label:string,hours:float,cost:float}>
     */
    private function buildRows(): array
    {
        [$fromDate, $toDate] = $this->normalizedDateRange();
        $entries = app(TimecardReportingService::class)->laborCostEntries(
            $this->projectId !== '' ? $this->projectId : null,
            $fromDate,
            $toDate,
        );

        if ($this->drillDown === 'week') {
            return $this->groupByWeek($entries);
        }

        if ($this->drillDown === 'employee') {
            return $this->groupByEmployee($entries);
        }

        return $this->groupByProject($entries);
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     * @return array<int, array{label:string,hours:float,cost:float}>
     */
    private function groupByProject(Collection $entries): array
    {
        $grouped = $entries->groupBy('project_id');
        $rows = [];

        foreach ($grouped as $projectId => $group) {
            $project = $group->first()->project;
            $hours = (float) $group->sum('hours');
            $rows[] = [
                'label' => $project->project_number
                    ? $project->project_number.' - '.$project->name
                    : $project->name,
                'hours' => round($hours, 2),
                'cost' => $this->sumEntryCosts($group),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['hours'] <=> $a['hours']);

        return $rows;
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     * @return array<int, array{label:string,hours:float,cost:float}>
     */
    private function groupByWeek(Collection $entries): array
    {
        $grouped = $entries->groupBy(fn ($entry): string => $entry->date->startOfWeek()->toDateString());
        $rows = [];

        foreach ($grouped as $weekStart => $group) {
            $hours = (float) $group->sum('hours');
            $rows[] = [
                'label' => 'Week of '.date('M d, Y', strtotime((string) $weekStart)),
                'hours' => round($hours, 2),
                'cost' => $this->sumEntryCosts($group),
            ];
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     * @return array<int, array{label:string,hours:float,cost:float}>
     */
    private function groupByEmployee(Collection $entries): array
    {
        $grouped = $entries->groupBy('user_id');
        $rows = [];

        foreach ($grouped as $userId => $group) {
            $user = $group->first()->user;
            $hours = (float) $group->sum('hours');
            $userLabel = trim((string) (($user?->first_name ?? '').' '.($user?->last_name ?? '')));
            $rows[] = [
                'label' => $userLabel !== '' ? $userLabel : ($user?->username ?? 'Unknown'),
                'hours' => round($hours, 2),
                'cost' => $this->sumEntryCosts($group),
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['hours'] <=> $a['hours']);

        return $rows;
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     */
    private function sumEntryCosts(Collection $entries): float
    {
        return 0.0;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function normalizedDateRange(): array
    {
        $fromDate = filled($this->fromDate) ? (string) $this->fromDate : null;
        $toDate = filled($this->toDate) ? (string) $this->toDate : null;

        if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
            return [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }
}
