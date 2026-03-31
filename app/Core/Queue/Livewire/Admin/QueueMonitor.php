<?php

namespace App\Core\Queue\Livewire\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use romanzipp\QueueMonitor\Models\Monitor;

#[Layout('layouts.admin')]
#[Title('Queue Monitor')]
class QueueMonitor extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('queue.manage');
    }

    public function processQueue(): void
    {
        $this->authorize('queue.manage');

        try {
            Artisan::call('queue:work', [
                'connection' => 'database',
                '--stop-when-empty' => true,
                '--tries' => 3,
                '--max-jobs' => 100,
            ]);

            session()->flash('success', 'Queue processing started.');
        } catch (\Exception $e) {
            Log::error('Queue process error: '.$e->getMessage());
            session()->flash('error', 'Failed to process queue: '.$e->getMessage());
        }
    }

    public function retryFailed(): void
    {
        $this->authorize('queue.manage');

        try {
            Artisan::call('queue:retry all');
            session()->flash('success', 'Failed jobs have been queued for retry.');
        } catch (\Exception $e) {
            Log::error('Queue retry error: '.$e->getMessage());
            session()->flash('error', 'Failed to retry jobs: '.$e->getMessage());
        }
    }

    public function flushFailed(): void
    {
        $this->authorize('queue.manage');

        try {
            Artisan::call('queue:flush');
            session()->flash('success', 'Failed jobs have been flushed from the queue.');
        } catch (\Exception $e) {
            Log::error('Queue flush error: '.$e->getMessage());
            session()->flash('error', 'Failed to flush jobs: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        $pendingJobs = 0;
        $failedJobs = 0;
        $totalMonitoredJobs = 0;
        $recentJobs = collect([]);
        $runningJobs = 0;
        $completedJobs = 0;
        $failedMonitoredJobs = 0;
        $avgExecutionTime = null;
        $latestRun = null;

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
            $totalMonitoredJobs = Monitor::count();
            $recentJobs = Monitor::query()
                ->orderBy('started_at', 'desc')
                ->limit(10)
                ->get();

            $runningJobs = Monitor::where('status', 'running')->count();
            $completedJobs = Monitor::where('status', 'succeeded')->count();
            $failedMonitoredJobs = Monitor::where('status', 'failed')->count();

            $avgExecutionTime = Monitor::where('status', 'succeeded')
                ->where('started_at', '>=', now()->subDays(7))
                ->whereNotNull('finished_at')
                ->whereNotNull('started_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MICROSECOND, started_at, finished_at) / 1000) as avg_time')
                ->value('avg_time');
        } catch (\Exception $e) {
            Log::warning('Could not fetch monitor data: '.$e->getMessage());
        }

        try {
            if (file_exists(storage_path('logs/queue-worker.log'))) {
                $latestRun = Carbon::parse(filemtime(storage_path('logs/queue-worker.log')))->diffForHumans();
            }
        } catch (\Exception $e) {
            Log::warning('Could not read queue log file: '.$e->getMessage());
        }

        $successRate = $totalMonitoredJobs > 0
            ? round(($completedJobs / $totalMonitoredJobs) * 100, 1)
            : 100;

        return view('queue::livewire.admin.queue-monitor', [
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'latestRun' => $latestRun,
            'totalMonitoredJobs' => $totalMonitoredJobs,
            'recentJobs' => $recentJobs,
            'runningJobs' => $runningJobs,
            'completedJobs' => $completedJobs,
            'failedMonitoredJobs' => $failedMonitoredJobs,
            'avgExecutionTime' => $avgExecutionTime,
            'successRate' => $successRate,
        ]);
    }
}
