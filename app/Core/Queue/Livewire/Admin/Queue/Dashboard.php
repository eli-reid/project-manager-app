<?php

namespace App\Core\Queue\Livewire\Admin\Queue;

use App\Core\Queue\Services\QueueManagerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Queue Manager')]
class Dashboard extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $activeTab = 'pending';

    public string $historyFilter = 'all';

    public function mount(): void
    {
        $this->authorize('queue.viewAny');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedHistoryFilter(): void
    {
        $this->resetPage();
    }

    public function retryJob(string $uuid): void
    {
        $this->authorize('queue.manage');

        app(QueueManagerService::class)->retryFailedJob($uuid);

        session()->flash('success', 'Failed job queued for retry.');
    }

    public function retryAllFailed(): void
    {
        $this->authorize('queue.manage');

        app(QueueManagerService::class)->retryAllFailedJobs();

        session()->flash('success', 'All failed jobs queued for retry.');
    }

    public function runNextJob(): void
    {
        $this->authorize('queue.manage');

        app(QueueManagerService::class)->runNextJob();

        session()->flash('success', 'Processed the next available queued job.');
    }

    public function deleteJob(string $uuid): void
    {
        $this->authorize('queue.manage');

        app(QueueManagerService::class)->deleteFailedJob($uuid);

        session()->flash('success', 'Failed job deleted.');
    }

    public function clearAllFailed(): void
    {
        $this->authorize('queue.manage');

        app(QueueManagerService::class)->clearAllFailedJobs();

        session()->flash('success', 'All failed jobs cleared.');
    }

    public function clearHistory(): void
    {
        $this->authorize('queue.manage');

        $deletedCount = app(QueueManagerService::class)->clearHistory($this->historyFilter);
        $scope = $this->historyFilter === 'all' ? 'all' : $this->historyFilter;

        session()->flash('success', "Cleared {$deletedCount} {$scope} history record(s).");

        $this->resetPage();
    }

    public function render()
    {
        $service = app(QueueManagerService::class);

        $jobs = $this->activeTab === 'pending' ? $service->getJobs() : null;
        $failedJobs = $this->activeTab === 'failed' ? $service->getFailedJobs() : null;
        $history = $this->activeTab === 'history' ? $service->getHistory($this->historyFilter) : null;
        $batches = $this->activeTab === 'batches' ? $service->getBatches() : null;

        $viewName = collect([
            'queue-manager::livewire.admin.queue.dashboard',
            'queue-manager::Livewire.Admin.queue.dashboard',
            'livewire.admin.queue.dashboard',
        ])->first(static fn (string $candidate): bool => view()->exists($candidate))
            ?? 'queue-manager::Livewire.Admin.queue.dashboard';

        return view($viewName, [
            'stats' => $service->getStats(),
            'jobs' => $jobs,
            'failedJobs' => $failedJobs,
            'history' => $history,
            'batches' => $batches,
        ]);
    }
}
