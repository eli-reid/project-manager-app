<?php

namespace App\Core\Settings\Contracts;

use App\Core\Settings\DTO\Setting;

interface ClassSettingsProvider
{
    /**
     * Return an array of Setting DTOs describing domain settings.
     *
     * @return Setting[]
     */
    public static function definitions(): array;
}
