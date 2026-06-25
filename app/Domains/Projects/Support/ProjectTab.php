<?php

declare(strict_types=1);

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTab As ProjectTabInterface;
use App\Domains\Projects\Contracts\ProjectTabPanel;
use App\Domains\Projects\Models\Project;

abstract class ProjectTab implements ProjectTabInterface
{
    public function __construct(
        private readonly string $key,
        private readonly string $label = '',
        private readonly int $sort = 100,
        private readonly ?string $modeParam = null,
        private readonly ?string $detailQueryParam = null,
        private readonly ?ProjectTabPanel $panel = null,
    ) {
        if ($key === '') {
            throw new \InvalidArgumentException('ProjectTab requires a non-empty key');
        }
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label !== ''
            ? $this->label
            : (string) \ucwords((string) \str_replace(['-', '_', '.'], ' ', $this->key));
    }

    public function sort(): int
    {
        return $this->sort;
    }

    public function modeQueryParam(): ?string
    {
        return $this->modeParam;
    }

    public function detailQueryParam(): ?string
    {
        return $this->detailQueryParam;
    }

    public function panel(): ?ProjectTabPanel
    {
        return $this->panel;
    }

    abstract public function isVisible(User $user, Project $project): bool;

    abstract public function badgeCount(User $user, Project $project): ?int;
}
