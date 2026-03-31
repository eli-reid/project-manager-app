<?php

namespace App\Core\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use romanzipp\QueueMonitor\Models\Monitor;

class QueueController extends Controller
{
    /**
     * Process the queue immediately.
     *
     * @return RedirectResponse
     */
    public function store()
    {
        try {
            // Get before counts from both traditional tables and monitor
            $beforeCount = 0;
            $beforeFailedCount = 0;
            $beforeMonitoredJobs = 0;

            try {
                $beforeCount = DB::table('jobs')->count();
                $beforeFailedCount = DB::table('failed_jobs')->count();
                $beforeMonitoredJobs = Monitor::count();
            } catch (\Exception $e) {
                Log::warning('Could not get initial queue counts: '.$e->getMessage());
            }

            // Log the queue processing attempt
            Log::info('Queue processing started', [
                'pending_jobs' => $beforeCount,
                'failed_jobs' => $beforeFailedCount,
                'total_monitored' => $beforeMonitoredJobs,
                'triggered_by' => Auth::user()->name ?? 'System',
                'timestamp' => now()->toISOString(),
            ]);

            // Process the queue with enhanced parameters
            $exitCode = Artisan::call('queue:work', [
                'connection' => 'database',  // Specify database connection
                '--stop-when-empty' => true,
                '--tries' => 3,
                '--max-jobs' => 100,  // Increased from 50
                '--timeout' => 300,   // 5 minutes timeout
                '--memory' => 512,    // Memory limit
                '--queue' => 'default,emails,scheduled-tasks', // Process all queues in priority order
            ]);

            // Get after counts
            $afterCount = 0;
            $afterFailedCount = 0;
            $afterMonitoredJobs = 0;
            $recentSucceeded = 0;
            $recentFailed = 0;

            try {
                $afterCount = DB::table('jobs')->count();
                $afterFailedCount = DB::table('failed_jobs')->count();
                $afterMonitoredJobs = Monitor::count();

                // Get recent job results from monitor
                $recentSucceeded = Monitor::where('status', 'succeeded')
                    ->where('started_at', '>=', now()->subMinutes(5))
                    ->count();

                $recentFailed = Monitor::where('status', 'failed')
                    ->where('started_at', '>=', now()->subMinutes(5))
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Could not get final queue counts: '.$e->getMessage());
            }

            $processedJobs = $beforeCount - $afterCount;
            $newFailedJobs = $afterFailedCount - $beforeFailedCount;
            $totalNewMonitoredJobs = $afterMonitoredJobs - $beforeMonitoredJobs;

            // Log the results
            Log::info('Queue processing completed', [
                'exit_code' => $exitCode,
                'jobs_processed' => $processedJobs,
                'new_failed_jobs' => $newFailedJobs,
                'remaining_jobs' => $afterCount,
                'recent_succeeded' => $recentSucceeded,
                'recent_failed' => $recentFailed,
                'total_new_monitored' => $totalNewMonitoredJobs,
                'duration' => 'immediate',
                'timestamp' => now()->toISOString(),
            ]);

            // Create detailed success message
            if ($processedJobs > 0 || $recentSucceeded > 0) {
                $message = 'Queue processed successfully! ';

                if ($processedJobs > 0) {
                    $message .= "Processed {$processedJobs} pending jobs. ";
                }

                if ($recentSucceeded > 0) {
                    $message .= "Successfully completed {$recentSucceeded} jobs. ";
                }

                if ($recentFailed > 0) {
                    $message .= "⚠️ {$recentFailed} jobs failed - check the Failed Jobs section. ";
                }

                if ($afterCount > 0) {
                    $message .= "{$afterCount} jobs remain in queue.";
                } else {
                    $message .= 'Queue is now empty! ✅';
                }

                return redirect()->back()->with('success', trim($message));
            } else {
                $message = 'No jobs were processed. ';

                if ($afterCount > 0) {
                    $message .= "There are {$afterCount} jobs in the queue that may require attention or are scheduled for later.";
                } else {
                    $message .= 'Queue is currently empty.';
                }

                return redirect()->back()->with('info', $message);
            }
        } catch (\Exception $e) {
            Log::error('Queue processing failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user' => Auth::user()->name ?? 'System',
                'timestamp' => now()->toISOString(),
            ]);

            return redirect()->back()->with('error', 'Failed to process queue: '.$e->getMessage());
        }
    }
}
