<?php

return [
    [
        'key' => 'invoices.pdf_import.run_queue_synchronously',
        'value' => env('INVOICES_PDF_IMPORT_RUN_QUEUE_SYNCHRONOUSLY', false) ? 'true' : 'false',
        'display_name' => 'Process PDF Imports Immediately',
        'description' => 'Run the queue worker inline after each PDF import upload instead of waiting for the next scheduled queue run. Useful on shared hosting where the queue only processes every few minutes.',
        'type' => 'select',
        'group' => 'invoices',
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
];
