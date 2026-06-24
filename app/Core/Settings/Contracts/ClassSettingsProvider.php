<?php

namespace App\Core\Settings\Contracts;

use App\Core\Settings\DTO\Setting;

/**
 * Unified settings provider contract.
 *
 * Implement this when a domain exposes settings via a PHP class. Providers may
 * return an array of `Setting` DTOs or an array shape describing settings.
 */
interface SettingsProvider
{
    /**
     * Return an array of settings definitions.
     *
     * Each item may be either a `Setting` DTO or an array shape with keys such
     * as `key`, `value`, `default_value`, `display_name`, etc.
     *
     * @return array<int, Setting|array<string,mixed>>
     */
    public static function definitions(): array;
}
