<?php

namespace App\Core\Settings\Abstracts;

use App\Core\Settings\Contracts\ClassSettingsProvider;
use App\Core\Settings\DTO\Setting;

abstract class AbstractSettingsProvider implements ClassSettingsProvider
{
    /**
     * Helper to convert legacy array definitions into Setting DTOs.
     *
     * @param  array<int, array<string,mixed>>  $definitions
     * @return Setting[]
     */
    protected static function fromArrayDefinitions(array $definitions): array
    {
        $result = [];

        foreach ($definitions as $def) {
            $result[] = new Setting(
                key: $def['key'] ?? (string) ($def['name'] ?? ''),
                type: $def['type'] ?? 'string',
                formFieldType: $def['form_field_type'] ?? $def['type'] ?? 'text',
                value: $def['value'] ?? $def['default_value'] ?? null,
                display_name: $def['display_name'] ?? null,
                description: $def['description'] ?? null,
                group: $def['group'] ?? null,
                options: $def['options'] ?? null,
                order: (int) ($def['order'] ?? 100),
                is_visible: (bool) ($def['is_visible'] ?? true),
                is_public: (bool) ($def['is_public'] ?? false),
                is_required: (bool) ($def['is_required'] ?? false),
                encrypted: (bool) ($def['encrypted'] ?? false),
            );
        }

        return $result;
    }
}
