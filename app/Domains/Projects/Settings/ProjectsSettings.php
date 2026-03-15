<?php

namespace App\Domains\Projects\Settings;

use App\Core\Settings\Contracts\DomainSettingsProvider;

class ProjectsSettings implements DomainSettingsProvider
{
    public static function settings(): array
    {
        return [
            [
                'key' => 'projects.auto_generate_numbers',
                'value' => 'true',
                'display_name' => 'Auto Generate Project Numbers',
                'description' => 'Automatically assign a project number when one is not entered manually.',
                'type' => 'select',
                'group' => 'projects',
                'options' => [
                    'true' => 'Yes',
                    'false' => 'No',
                ],
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'projects.number_prefix',
                'value' => 'PRJ-',
                'display_name' => 'Project Number Prefix',
                'description' => 'Prefix prepended to auto-generated project numbers.',
                'type' => 'text',
                'group' => 'projects',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }
}
