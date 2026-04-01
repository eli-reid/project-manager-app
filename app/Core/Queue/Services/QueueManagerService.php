<?php

namespace App\Core\Queue\Services;

use App\Core\Queue\Models\FailedJob;
use App\Core\Queue\Models\JobBatch;
use App\Core\Queue\Models\QueueJob;
use App\Core\Queue\Models\QueueJobHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;

class QueueManagerService
{
    public function getStats(): array
    {
        return [
            'pending' => QueueJob::query()->count(),
            'running' => QueueJob::query()->whereNotNull('reserved_at')->count(),
            'failed' => FailedJob::query()->count(),
            'completed_today' => QueueJobHistory::query()
                ->where('status', 'completed')
                ->whereDate('started_at', now()->toDateString())
                ->count(),
            'batches' => JobBatch::query()->count(),
        ];
    }

    public function getJobs(int $perPage = 10): LengthAwarePaginator
    {
        return QueueJob::query()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(function (QueueJob $job): QueueJob {
                $payload = json_decode((string) $job->payload, true);
                $job->decoded_job_class = $payload['displayName'] ?? $payload['job'] ?? 'UnknownJob';

                return $job;
            });
    }

    public function getFailedJobs(int $perPage = 10): LengthAwarePaginator
    {
        return FailedJob::query()
            ->orderByDesc('failed_at')
            ->paginate($perPage)
            ->through(function (FailedJob $job): FailedJob {
                $payload = json_decode((string) $job->payload, true);
                $job->decoded_job_class = $payload['displayName'] ?? $payload['job'] ?? 'UnknownJob';

                return $job;
            });
    }

    public function getHistory(string $status = 'all', int $perPage = 10): LengthAwarePaginator
    {
        return QueueJobHistory::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('started_at')
            ->paginate($perPage);
    }

    public function getBatches(int $perPage = 10): LengthAwarePaginator
    {
        return JobBatch::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function retryFailedJob(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    public function retryAllFailedJobs(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
    }

    public function runNextJob(): void
    {
        Artisan::call('queue:work', ['--once' => true]);
    }

    public function deleteFailedJob(string $uuid): void
    {
        FailedJob::query()->where('uuid', $uuid)->delete();
    }

    public function clearAllFailedJobs(): void
    {
        Artisan::call('queue:flush');
    }
}
