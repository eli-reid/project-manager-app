<?php

namespace App\Core\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use romanzipp\QueueMonitor\Models\Monitor;

class QueueMonitorController extends Controller
{
    public function index()
    {
        try {
            // Get data from both old queue tables and new monitor table
            $pendingJobs = 0;
            $failedJobs = 0;
            $latestJob = null;

            try {
                $pendingJobs = DB::table('jobs')->count();
            } catch (\Exception $e) {
                Log::warning('Could not fetch pending jobs count: '.$e->getMessage());
            }

            try {
                $failedJobs = DB::table('failed_jobs')->count();
            } catch (\Exception $e) {
                Log::warning('Could not fetch failed jobs count: '.$e->getMessage());
            }

            try {
                $latestJob = DB::table('jobs')
                    ->orderBy('created_at', 'desc')
                    ->first();
            } catch (\Exception $e) {
                Log::warning('Could not fetch latest job: '.$e->getMessage());
            }

            // Get monitored jobs data
            $totalMonitoredJobs = 0;
            $recentJobs = collect([]);
            $runningJobs = 0;
            $completedJobs = 0;
            $failedMonitoredJobs = 0;
            $avgExecutionTime = null;

            try {
                $totalMonitoredJobs = Monitor::count();
                $recentJobs = Monitor::with([])
                    ->orderBy('started_at', 'desc')
                    ->limit(10)
                    ->get();

                $runningJobs = Monitor::where('status', 'running')->count();
                $completedJobs = Monitor::where('status', 'succeeded')->count();
                $failedMonitoredJobs = Monitor::where('status', 'failed')->count();

                // Performance metrics - calculate elapsed time from timestamps
                $avgExecutionTime = Monitor::where('status', 'succeeded')
                    ->where('started_at', '>=', now()->subDays(7))
                    ->whereNotNull('finished_at')
                    ->whereNotNull('started_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MICROSECOND, started_at, finished_at) / 1000) as avg_time')
                    ->value('avg_time');
            } catch (\Exception $e) {
                Log::warning('Could not fetch monitor data: '.$e->getMessage());
            }

            $successRate = $totalMonitoredJobs > 0
                ? round(($completedJobs / $totalMonitoredJobs) * 100, 1)
                : 100;

            $latestRun = null;
            try {
                if (file_exists(storage_path('logs/queue-worker.log'))) {
                    $latestRun = Carbon::parse(filemtime(storage_path('logs/queue-worker.log')))->diffForHumans();
                }
            } catch (\Exception $e) {
                Log::warning('Could not read queue log file: '.$e->getMessage());
            }

            return view('admin.queue-monitor', [
                'pendingJobs' => $pendingJobs,
                'failedJobs' => $failedJobs,
                'latestJob' => $latestJob,
                'latestRun' => $latestRun,
                // New monitoring data
                'totalMonitoredJobs' => $totalMonitoredJobs,
                'recentJobs' => $recentJobs,
                'runningJobs' => $runningJobs,
                'completedJobs' => $completedJobs,
                'failedMonitoredJobs' => $failedMonitoredJobs,
                'avgExecutionTime' => $avgExecutionTime,
                'successRate' => $successRate,
            ]);
        } catch (\Exception $e) {
            Log::error('Queue monitor error: '.$e->getMessage());

            return view('admin.queue-monitor', [
                'error' => $e->getMessage(),
                // Provide default values to prevent undefined variable errors
                'pendingJobs' => 0,
                'failedJobs' => 0,
                'latestJob' => null,
                'latestRun' => null,
                'totalMonitoredJobs' => 0,
                'recentJobs' => collect([]),
                'runningJobs' => 0,
                'completedJobs' => 0,
                'failedMonitoredJobs' => 0,
                'avgExecutionTime' => null,
                'successRate' => 100,
            ]);
        }
    }

    public function retry($id = null)
    {
        if ($id) {
            // Retry specific job
            \Artisan::call('queue:retry', ['id' => $id]);
        } else {
            // Retry all failed jobs
            \Artisan::call('queue:retry all');
        }

        return redirect()->route('admin.queue-monitor.index')
            ->with('success', 'Failed jobs have been queued for retry');
    }

    public function flush()
    {
        \Artisan::call('queue:flush');

        return redirect()->route('admin.queue-monitor.index')
            ->with('info', 'Failed jobs have been flushed from the queue');
    }
}
