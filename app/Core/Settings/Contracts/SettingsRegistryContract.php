<?php

declare(strict_types=1);

namespace App\Core\Settings\Contracts;

use App\Core\Settings\DTO\Setting;

/**
 * Unified settings provider contract.
 *
 * Implement this when a domain exposes settings via a PHP class. Providers may
 * return an array of `Setting` DTOs or an array shape describing settings.
 */
interface SettingsRegistryContract
{
    /**
     * Return an array of settings definitions.
     *
     * Each item is a `Setting` DTO.
     *
     * @return array<int, Setting>
     */
    public static function definitions(): array;
}
