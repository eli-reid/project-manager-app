<?php

use App\Core\Settings\DTO\Setting;
Use App\Core\Settings\DTO\SettingType;
Use App\Core\Settings\DTO\SettingFormFieldType;

return [
    'project' => [
        Setting::class => new Setting(
            key: 'projects.auto_generate_numbers',
            type: SettingType::Boolean,
            formFieldType: SettingFormFieldType::Toggle,
            value: 'active',
            display_name: 'Default Project Status',
            description: 'The default status assigned to new projects.',
            group: 'General',
            order: 10,
        ),
        Setting::class => new Setting(
            key: 'project.default_priority',
            type: SettingType::String,
            formFieldType: SettingFormFieldType::Select,
            value: 'medium',
            display_name: 'Default Project Priority',
            description: 'The default priority level for new projects.',
            group: 'General',
            options: [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
            ],
            order: 20,
        ),


    ],

];
 [
        'key' => 'projects.auto_generate_numbers',
        'value' => env('PROJECTS_AUTO_GENERATE_NUMBERS', true) ? 'true' : 'false',
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
        'value' => env('PROJECTS_NUMBER_PREFIX', 'PRJ-'),
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
    [
        'key' => 'projects.number_padding',
        'value' => env('PROJECTS_NUMBER_PADDING', 4),
        'display_name' => 'Project Number Padding',
        'description' => 'Number of digits for auto-generated project numbers.',
        'type' => 'number',
        'group' => 'projects',
        'order' => 3,
        'is_visible' => true,
        'is_public' => false,
        'is_required' => false,
        'encrypted' => false,
    ],
