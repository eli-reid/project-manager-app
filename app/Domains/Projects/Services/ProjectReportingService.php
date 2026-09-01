<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectReportingService
{
    /**
     * @return Collection<int, Project>
     */
    public function activeProjects(): Collection
    {
        return Project::query()
            ->select(['id', 'name', 'project_number', 'accounting_code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function exists(string $projectId): bool
    {
        return Project::query()->whereKey($projectId)->exists();
    }

    public function findSummary(string $projectId): ?Project
    {
        return Project::query()
            ->select(['id', 'name', 'project_number', 'accounting_code', 'status'])
            ->find($projectId);
    }
}
