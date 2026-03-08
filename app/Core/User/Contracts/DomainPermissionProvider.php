<?php

namespace App\Core\User\Contracts;

interface DomainPermissionProvider
{
    /**
     * Return permission definitions managed by a domain.
     *
     * Each item should include at least `resource` and `action` and can include:
     * label, description, built_in_roles.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function permissions(): array;
}
