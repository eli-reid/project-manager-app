#!/usr/local/bin/php -d register_argc_argv=On
<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\ArgvInput;

/*
|--------------------------------------------------------------------------
| Self-Perpetuating Queue Worker for Bluehost
|--------------------------------------------------------------------------
|
| Bluehost shared hosting only allows cron jobs every 15 minutes.
| This script works around that by running continuously and restarting itself.
|
| Bluehost cron job (every 15 minutes):
| [every 15 min] cd /path/to/app && /usr/local/bin/php -d register_argc_argv=On queue-worker.php >> storage/logs/queue-worker.log 2>&1
|
| The script will:
| 1. Process queue for 14 minutes
| 2. Exit gracefully
| 3. Cron starts it again every 15 minutes
| 4. Always have a running queue worker
|
*/

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and get the application instance
$app = require_once __DIR__.'/bootstrap/app.php';

// Create the kernel
$kernel = $app->make(Kernel::class);

// Bootstrap the application
$kernel->bootstrap();

// Log start
Log::info('Queue worker starting');

// Run the queue worker for 14 minutes (840 seconds)
// This gives 1 minute buffer before next cron run
$status = $kernel->call('queue:work', [
    'connection' => 'database',
    '--queue' => 'default,emails,scheduled-tasks',
    '--max-time' => 840, // Run for 14 minutes
    '--sleep' => 3,      // Sleep 3 seconds between jobs
    '--tries' => 3,      // Retry failed jobs 3 times
    '--timeout' => 300,  // 5 minute timeout per job
    '--rest' => 1,       // Rest 1 second between job batches
]);

// Log completion
Log::info('Queue worker completed cycle', ['status' => $status]);

// Terminate the application
$kernel->terminate(new ArgvInput, $status);

exit($status);
