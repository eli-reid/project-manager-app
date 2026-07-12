<?php

declare(strict_types=1);

namespace App\Core\Navigation\Services;

use App\Core\Navigation\DTO\NavGroup;
use App\Core\Navigation\DTO\NavItem;
use Illuminate\Support\Facades\Auth;

class NavigationManager
{
    /**
     * Sections structure:
     * [sectionKey => ['label' => string, 'order' => int, 'groups' => [...], 'items' => [...]]]
     *
     * @var array<string, array>
     */
    private array $sections = [];

    public function registerSection(string $key, string $label, int $order = 0): void
    {
        $this->sections[$key] = array_merge($this->sections[$key] ?? [], [
            'label' => $label,
            'order' => $order,
            'groups' => $this->sections[$key]['groups'] ?? [],
            'items' => $this->sections[$key]['items'] ?? [],
        ]);
    }

    public function registerGroup(string $sectionKey, NavGroup $group): void
    {
        $this->sections[$sectionKey]['groups'][$group->id] = $group;
    }

    public function registerItem(string $sectionKey, ?string $groupKey, NavItem $item): void
    {
        if ($groupKey) {
            $this->sections[$sectionKey]['groups'][$groupKey] = $this->sections[$sectionKey]['groups'][$groupKey] ?? null;
            // store items under group
            $this->sections[$sectionKey]['groups'][$groupKey]->items[] = $item;

            return;
        }

        $this->sections[$sectionKey]['items'][$item->id] = $item;
    }

    /**
     * Resolve sections into arrays suitable for rendering.
     * Applies ordering and simple visibility filtering.
     */
    public function resolve(): array
    {
        $resolved = [];
        foreach ($this->sections as $key => $section) {
            $groups = [];
            foreach ($section['groups'] ?? [] as $group) {
                $groupItems = [];
                foreach ($group->items() as $item) {
                    if ($this->isVisible($item)) {
                        $groupItems[] = $this->itemToArray($item);
                    }
                }
                $groups[] = [
                    'id' => $group->id,
                    'label' => $group->label,
                    'icon' => $group->icon,
                    'collapsed' => $group->collapsed,
                    'items' => $groupItems,
                    'order' => $group->order ?? 0,
                ];
            }

            $items = [];
            foreach ($section['items'] ?? [] as $item) {
                if ($this->isVisible($item)) {
                    $items[] = $this->itemToArray($item);
                }
            }

            usort($groups, function ($a, $b) {
                return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
            });

            usort($items, function ($a, $b) {
                return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
            });

            $resolved[] = [
                'key' => $key,
                'label' => $section['label'] ?? $key,
                'groups' => $groups,
                'items' => $items,
                'order' => $section['order'] ?? 0,
            ];
        }

        usort($resolved, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });

        return $resolved;
    }

    private function itemToArray(NavItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'icon' => $item->icon,
            'url' => $item->url,
            'route' => $item->route,
            'order' => $item->order,
            'meta' => $item->meta,
        ];
    }

    private function isVisible(NavItem $item): bool
    {
        if (! $item->visible) {
            return false;
        }

        if (empty($item->permissions)) {
            return true;
        }

        foreach ($item->permissions as $ability) {
            try {
                if (Auth::user()?->can($ability)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // swallow and continue
            }
        }

        return false;
    }
}
