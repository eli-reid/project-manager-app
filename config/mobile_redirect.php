<?php

return [
    // Exact route mappings that do not follow a simple namespace swap.
    'exact' => [
        'dashboard' => 'mobile.dashboard',
    ],

    // Desktop route prefix => Mobile route prefix.
    // Domain providers may append to this at boot.
    'prefix' => [],

    // Fallback route when a domain is registered but a specific mobile route is not available.
    'fallback' => 'mobile.dashboard',
];
