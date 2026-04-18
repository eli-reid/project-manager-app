<?php

return [
    [
        'key' => 'scheduler.claim_window_seconds',
        'value' => (string) env('SCHEDULER_CLAIM_WINDOW_SECONDS', 300),
        'display_name' => 'Scheduler Claim Window (seconds)',
        'description' => 'Time window (seconds) for preventing duplicate scheduled task dispatch. Increase for unreliable cron environments (e.g., Bluehost). Default: 300 (5 min). For shared hosting: 900-1800 (15-30 min).',
        'type' => 'number',
        'group' => 'scheduler',
        'order' => 1,
        'is_visible' => true,
        'is_public' => false,
        'is_required' => true,
        'encrypted' => false,
    ],
];
