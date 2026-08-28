<?php

namespace App\Domains\Projects\Services;

use App\Domains\Projects\Models\Project;

class ProjectTabLinkBuilder
{
    public function __construct(private readonly ProjectTabRegistry $projectTabRegistry) {}

    public function to(
        Project|string $project,
        string $tab,
        ?string $mode = null,
        ?string $detailId = null,
        array $extraQuery = [],
        bool $absolute = true,
    ): string {
        $query = ['tab' => $tab];

        $modeQueryParam = $this->projectTabRegistry->modeQueryParam($tab);
        if ($mode !== null && $mode !== '' && is_string($modeQueryParam) && $modeQueryParam !== '') {
            $query[$modeQueryParam] = $mode;
        }

        $detailQueryParam = $this->projectTabRegistry->detailQueryParam($tab);
        if ($detailId !== null && $detailId !== '' && is_string($detailQueryParam) && $detailQueryParam !== '') {
            $query[$detailQueryParam] = $detailId;
        }

        return route('admin.projects.show', [
            'project' => $project,
            ...$query,
            ...$extraQuery,
        ], $absolute);
    }
}
