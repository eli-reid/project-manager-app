<?php

declare(strict_types=1);

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTabInterface;
use App\Domains\Projects\Contracts\ProjectTabPanelInterface;
use App\Domains\Projects\Models\Project;

abstract readonly class AbstractProjectTab implements ProjectTabInterface
{
    public function __construct(
        private string $key,
        private string $label = '',
        private int $sort = 100,
        private ?string $modeParam = null,
        private ?string $detailQueryParam = null,
        private ?ProjectTabPanelInterface $panel = null,
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

    public function panel(): ?ProjectTabPanelInterface
    {
        return $this->panel;
    }

    abstract public function isVisible(User $user, Project $project): bool;

    abstract public function badgeCount(User $user, Project $project): ?int;
}
