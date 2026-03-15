<?php

namespace App\Domains\Tasks\Settings;

use App\Core\Settings\Contracts\DomainSettingsProvider;

class TasksSettings implements DomainSettingsProvider
{
    public static function settings(): array
    {
        return [
            [
                'key' => 'tasks.max_category_depth',
                'value' => '3',
                'display_name' => 'Maximum Category Depth',
                'description' => 'Maximum allowed nesting depth for task categories.',
                'type' => 'number',
                'group' => 'tasks',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'tasks.max_task_depth',
                'value' => '2',
                'display_name' => 'Maximum Task Depth',
                'description' => 'Maximum allowed nesting depth for parent-child task chains.',
                'type' => 'number',
                'group' => 'tasks',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }
}
