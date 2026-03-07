<?php

namespace App\Core\Settings\Contracts;

/**
 * Implement this contract in each domain to define settings defaults and metadata.
 */
interface DomainSettingsProvider
{
    /**
     * Return domain-managed settings definitions.
     *
     * Each item should include at least `key` and can include:
     * value, default_value, display_name, description, type, group, options,
     * order, is_public, is_visible, is_required, encrypted.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function settings(): array;
}
