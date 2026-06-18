<?php

declare(strict_types=1);

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Contracts\ProjectTabPanel;
use App\Domains\Projects\Models\Project;

final class ProjectTab
{
    public string $key;

    public string $label;

    public int $sort;

    public ?string $modeParam;

    public ?string $detailQueryParam;

    /** @var ProjectTabPanel|string|null */
    public $panel;

    /** @var callable(User, Project): ?int|null */
    private $badgeResolver;

    /** @var callable(User, Project): bool|null */
    private $visibilityResolver;

    /**
     * @param  callable(User, Project): ?int|null  $badgeResolver
     * @param  callable(User, Project): bool|null  $visibilityResolver
     */
    public function __construct(
        string $key,
        string $label = '',
        int $sort = 100,
        ?string $modeParam = null,
        ?string $detailQueryParam = null,
        ProjectTabPanel|string|null $panel = null,
        ?callable $badgeResolver = null,
        ?callable $visibilityResolver = null,
    ) {
        if ($key === '') {
            throw new \InvalidArgumentException('ProjectTab requires a non-empty key');
        }

        $this->key = $key;
        $this->label = $label !== '' ? $label : (string) \ucwords((string) \str_replace(['-', '_', '.'], ' ', $key));
        $this->sort = $sort;
        $this->modeParam = $modeParam;
        $this->detailQueryParam = $detailQueryParam;
        $this->panel = $panel;
        $this->badgeResolver = $badgeResolver;
        $this->visibilityResolver = $visibilityResolver;
    }

    /**
     * Normalize an array or instance into a ProjectTab.
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
        $sort = isset($definition['sort']) ? (int) $definition['sort'] : 100;
        $modeParam = $definition['mode_param'] ?? null;
        $detailQueryParam = $definition['detail_query_param'] ?? null;
        $panel = $definition['panel'] ?? null;
        $badge = $definition['badge_count'] ?? null;
        $isVisible = $definition['is_visible'] ?? null;

        return new self(
            $key,
            $label,
            $sort,
            is_string($modeParam) && $modeParam !== '' ? $modeParam : null,
            is_string($detailQueryParam) && $detailQueryParam !== '' ? $detailQueryParam : null,
            $panel,
            is_callable($badge) ? $badge : null,
            is_callable($isVisible) ? $isVisible : null,
        );
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        if (! is_callable($this->badgeResolver)) {
            return null;
        }

        $result = ($this->badgeResolver)($user, $project);

        return is_int($result) ? $result : null;
    }

    public function isVisible(User $user, Project $project): bool
    {
        if (! is_callable($this->visibilityResolver)) {
            return false;
        }

        return (bool) ($this->visibilityResolver)($user, $project);
    }

    public function panelInstance(): ?ProjectTabPanel
    {
        $panel = $this->panel;
        if (is_string($panel) && class_exists($panel) && is_subclass_of($panel, ProjectTabPanel::class)) {
            return app($panel);
        }

        return $panel instanceof ProjectTabPanel ? $panel : null;
    }

    /**
     * Convert to array shape used by registry APIs.
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
            'badge_count' => $this->badgeResolver,
            'is_visible' => $this->visibilityResolver,
        ];
    }
}
