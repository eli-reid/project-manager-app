<?php

declare(strict_types=1);

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTabPanel;
use App\Domains\Projects\Models\Project;

final class ProjectTabSpec
{
    public string $key;

    public string $label;

    public int $sort;

    public ?string $modeParam;

    public ?string $detailQueryParam;

    /** @var ProjectTabPanel|string|null */
    public $panel;

    /** @var callable(User, Project): ?int */
    public $badgeCount;

    /** @var callable(User, Project): bool */
    public $isVisible;

    /**
     * @param  callable(User, Project): ?int|null  $badgeCount
     * @param  callable(User, Project): bool|null  $isVisible
     */
    public function __construct(
        string $key,
        string $label,
        int $sort = 100,
        ?string $modeParam = null,
        ?string $detailQueryParam = null,
        ProjectTabPanel|string|null $panel = null,
        ?callable $badgeCount = null,
        ?callable $isVisible = null,
    ) {
        if ($key === '') {
            throw new \InvalidArgumentException('ProjectTabSpec requires a non-empty key');
        }

        $this->key = $key;
        $this->label = $label ?: (string) \ucwords((string) \str_replace(['-', '_', '.'], ' ', $key));
        $this->sort = $sort;
        $this->modeParam = $modeParam;
        $this->detailQueryParam = $detailQueryParam;
        $this->panel = $panel;
        $this->badgeCount = $badgeCount ?? static fn (User $user, Project $project): ?int => null;
        $this->isVisible = $isVisible ?? static fn (User $user, Project $project): bool => false;
    }

    /**
     * Normalize an array or instance into a ProjectTabSpec.
     *
     * @param  array<string, mixed>|self  $definition
     */
    public static function from(array|self $definition): self
    {
        if ($definition instanceof self) {
            return $definition;
        }

        $key = (string) ($definition['key'] ?? '');
        if ($key === '') {
            throw new \InvalidArgumentException('Project tab definition must include a non-empty "key".');
        }

        $label = (string) ($definition['label'] ?? '');
        if ($label === '') {
            $label = (string) \ucwords((string) \str_replace(['-', '_', '.'], ' ', $key));
        }

        $sort = isset($definition['sort']) ? (int) $definition['sort'] : 100;
        $modeParam = $definition['mode_param'] ?? null;
        $detailQueryParam = $definition['detail_query_param'] ?? null;
        $panel = $definition['panel'] ?? null;
        $badgeCount = $definition['badge_count'] ?? null;
        $isVisible = $definition['is_visible'] ?? null;

        return new self(
            $key,
            $label,
            $sort,
            is_string($modeParam) && $modeParam !== '' ? $modeParam : null,
            is_string($detailQueryParam) && $detailQueryParam !== '' ? $detailQueryParam : null,
            $panel,
            is_callable($badgeCount) ? $badgeCount : null,
            is_callable($isVisible) ? $isVisible : null,
        );
    }

    /**
     * Convert back to array shape used by the registry.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'sort' => $this->sort,
            'mode_param' => $this->modeParam,
            'detail_query_param' => $this->detailQueryParam,
            'panel' => $this->panel,
            'badge_count' => $this->badgeCount,
            'is_visible' => $this->isVisible,
        ];
    }
}
