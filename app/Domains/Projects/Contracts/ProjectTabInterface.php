<?php

declare(strict_types=1);

namespace App\Domains\Projects\Contracts;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;

interface ProjectTabInterface
{
    public function key(): string;

    public function label(): string;

    public function sort(): int;

    public function modeQueryParam(): ?string;

    public function detailQueryParam(): ?string;

    public function panel(): ?ProjectTabPanelInterface;

    public function isVisible(User $user, Project $project): bool;

    public function badgeCount(User $user, Project $project): ?int;
}
