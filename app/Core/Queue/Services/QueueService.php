<?php

namespace App\Core\Queue\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QueueService
{
    /**
     * Process queued jobs immediately
     *
     * @param  int  $jobCount  Number of jobs to process
     * @return bool Success indicator
     */
    public function processImmediately(int $jobCount = 1): bool
    {
        try {
            Log::info("Processing {$jobCount} queued jobs immediately");

            $result = Artisan::call('queue:work', [
                'connection' => 'database',  // Specify database connection
                '--queue' => 'default,emails,scheduled-tasks', // Process all queues in priority order
                '--once' => $jobCount === 1, // If only one job, use --once flag
                '--max-jobs' => $jobCount,   // Otherwise process specified number of jobs
                '--stop-when-empty' => true, // Stop when queue is empty
                '--tries' => 3,               // Number of retry attempts
            ]);

            Log::info("Queue processing complete with exit code: {$result}");

            return $result === 0;
        } catch (\Exception $e) {
            Log::error('Error processing queue: '.$e->getMessage());

            return false;
        }
    }
}
