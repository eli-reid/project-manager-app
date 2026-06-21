<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Contracts\ProjectContext;
use App\Domains\Projects\Models\Project;
use Carbon\CarbonInterface;

final class ProjectContextService implements ProjectContext
{
    public function __construct(private Project $project)
    {
    }

    public function id(): string
    {
        return (string) $this->project->getKey();
    }

    public function name(): string
    {
        return (string) $this->project->name;
    }

    public function number(): ?string
    {
        return $this->project->project_number ?? null;
    }

    public function status(): string
    {
        return (string) $this->project->status;
    }

    public function startsAt(): ?CarbonInterface
    {
        return $this->project->start_date;
    }

    public function endsAt(): ?CarbonInterface
    {
        return $this->project->end_date;
    }

    public function isActive(): bool
    {
        return (bool) $this->project->is_active;
    }

    public function clientId(): ?string
    {
        // client_id is intentionally not exposed from ProjectContext.
        // Client relationships are managed by plugins outside the Project domain.
        return null;
    }
}
