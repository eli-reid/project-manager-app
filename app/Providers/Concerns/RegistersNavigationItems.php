<?php

declare(strict_types=1);

namespace App\Providers\Concerns;

use App\Core\UI\Navigation\DTO\NavItem;
use App\Core\UI\Navigation\DTO\NavSectionEnum;
use App\Core\UI\Navigation\Services\NavigationManager;

trait RegistersNavigationItems
{
    /**
     * @param  array<int, string|array<string, mixed>>  $permissions
     * @param  array<string, mixed>  $meta
     */
    protected function registerAdminNavigationItem(
        NavigationManager $navigationManager,
        string $id,
        string $label,
        string $route,
        ?string $icon,
        int $order,
        array $permissions = [],
        array $meta = [],
    ): void {
        $this->registerNavigationItem(
            $navigationManager,
            sectionKey: NavSectionEnum::ADMIN->value,
            sectionLabel: 'Administration',
            sectionOrder: 20,
            id: $id,
            label: $label,
            route: $route,
            icon: $icon,
            order: $order,
            permissions: $permissions,
            section: NavSectionEnum::ADMIN,
            meta: $meta,
        );
    }

    /**
     * @param  array<int, string|array<string, mixed>>  $permissions
     * @param  array<string, mixed>  $meta
     */
    protected function registerUserNavigationItem(
        NavigationManager $navigationManager,
        string $id,
        string $label,
        string $route,
        ?string $icon,
        int $order,
        array $permissions = [],
        array $meta = [],
    ): void {
        $this->registerNavigationItem(
            $navigationManager,
            sectionKey: NavSectionEnum::USER->value,
            sectionLabel: 'My Workspace',
            sectionOrder: 10,
            id: $id,
            label: $label,
            route: $route,
            icon: $icon,
            order: $order,
            permissions: $permissions,
            section: NavSectionEnum::USER,
            meta: $meta,
        );
    }

    /**
     * @return array{ability: string, model: class-string}
     */
    protected function policyPermission(string $ability, string $model): array
    {
        return [
            'ability' => $ability,
            'model' => $model,
        ];
    }

    /**
     * @param  array<int, mixed>  $arguments
     * @return array{ability: string, arguments?: array<int, mixed>}
     */
    protected function gatePermission(string $ability, array $arguments = []): array
    {
        if ($arguments === []) {
            return ['ability' => $ability];
        }

        return [
            'ability' => $ability,
            'arguments' => $arguments,
        ];
    }

    /**
     * @param  array<int, string|array<string, mixed>>  $permissions
     * @param  array<string, mixed>  $meta
     */
    private function registerNavigationItem(
        NavigationManager $navigationManager,
        string $sectionKey,
        string $sectionLabel,
        int $sectionOrder,
        string $id,
        string $label,
        string $route,
        ?string $icon,
        int $order,
        array $permissions,
        NavSectionEnum $section,
        array $meta,
    ): void {
        $navigationManager->registerSection($sectionKey, $sectionLabel, $sectionOrder);

        $navigationManager->registerItem($sectionKey, null, new NavItem(
            id: $id,
            label: $label,
            icon: $icon,
            url: null,
            route: $route,
            group: null,
            order: $order,
            active: false,
            visible: true,
            permissions: $permissions,
            section: $section,
            meta: $meta,
        ));
    }
}
