<?php

return [
    'class_discover_paths' => env('SETTINGS_CLASS_DISCOVER_PATHS') ? explode(',', env('SETTINGS_CLASS_DISCOVER_PATHS')) : [
        'app/Core/*/Settings',
        'app/Domains/*/Settings',
        'app/Plugins/*/Settings',
    ],
    
];
