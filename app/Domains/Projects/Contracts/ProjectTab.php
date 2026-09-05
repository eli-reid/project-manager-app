<?php

namespace App\Domains\Projects\Contracts;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;

interface ProjectTab
{
    public function key(): string;

    public function label(): string;

    public function sort(): int;

    public function modeQueryParam(): ?string;

    public function detailQueryParam(): ?string;

    public function panel(): ?ProjectTabPanel;

    public function isVisible(User $user, Project $project): bool;

    /**
     * @return array<int|string, mixed>
     */
    public function badgeCountRelations(User $user, Project $project): array;

    public function badgeCount(User $user, Project $project): ?int;
}
