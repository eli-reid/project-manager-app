<?php

declare(strict_types=1);

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTabInterface;
use App\Domains\Projects\Contracts\ProjectTabPanelInterface;
use App\Domains\Projects\Models\Project;

final class ResolvedProjectTab
{
    public function __construct(
        private readonly ProjectTabInterface $tab,
        private readonly string $label,
        private readonly int $sort,
        private readonly ?string $modeQueryParam,
        private readonly ?string $detailQueryParam,
        private readonly bool $isActive = true,
    ) {}

    public static function from(ProjectTabInterface $tab): self
    {
        return new self(
            tab: $tab,
            label: $tab->label(),
            sort: $tab->sort(),
            modeQueryParam: $tab->modeQueryParam(),
            detailQueryParam: $tab->detailQueryParam(),
        );
    }

    public function withOverrides(
        ?string $label = null,
        ?int $sort = null,
        ?string $modeQueryParam = null,
        ?bool $isActive = null,
    ): self {
        return new self(
            tab: $this->tab,
            label: $label !== null && $label !== '' ? $label : $this->label,
            sort: $sort ?? $this->sort,
            modeQueryParam: $modeQueryParam ?? $this->modeQueryParam,
            detailQueryParam: $this->detailQueryParam,
            isActive: $isActive ?? $this->isActive,
        );
    }

    public function key(): string
    {
        return $this->tab->key();
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
        return $this->tab->panel();
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $this->tab->isVisible($user, $project);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return $this->tab->badgeCount($user, $project);
    }
}
