<?php 

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTabInterface;
use App\Domains\Projects\Contracts\ProjectTabPanelInterface;
use App\Domains\Projects\Models\Project;


class ProjectTab implements ProjectTabInterface
{
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly int $sort,
        private readonly ?string $modeQueryParam = null,
        private readonly ?string $detailQueryParam = null,
        private readonly ?ProjectTabPanelInterface $panel = null,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function sort(): int
    {
        return $this->sort;
    }

    public function modeQueryParam(): ?string
    {
        return $this->modeQueryParam;
    }

    public function detailQueryParam(): ?string
    {
        return $this->detailQueryParam;
    }

    public function panel(): ?ProjectTabPanelInterface
    {
        return $this->panel;
    }

    public function isVisible(User $user, Project $project): bool
    {
        return true;
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return null;
    }
}