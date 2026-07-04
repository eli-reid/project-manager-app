<?php

return [
    'class_discover_paths' => env('SETTINGS_CLASS_DISCOVER_PATHS') ? explode(',', env('SETTINGS_CLASS_DISCOVER_PATHS')) : [
        'Settings',
        'Core\\*\\Settings',
        'Domains\\*\\Settings',
        'Plugins\\*\\Settings',
    ],

];
