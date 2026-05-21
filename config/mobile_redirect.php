<?php

return [
    // Exact route mappings that do not follow a simple namespace swap.
    'exact' => [
        'dashboard' => 'mobile.dashboard',
        'documents.index' => 'documents.mobile.global',
    ],

    // Desktop route prefix => Mobile route prefix.
    'prefix' => [
        'projects.' => 'projects.mobile.',
        'timecards.' => 'timecards.mobile.',
        'dailies.' => 'dailies.mobile.',
        'submittals.' => 'submittals.mobile.',
        'stock-orders.' => 'stock-orders.mobile.',
        'change-orders.' => 'change-orders.mobile.',
        'tasks.' => 'tasks.mobile.',
        'reports.' => 'reports.mobile.',
    ],

    // Fallback route when a domain is registered but a specific mobile route is not available.
    'fallback' => 'mobile.dashboard',
];
