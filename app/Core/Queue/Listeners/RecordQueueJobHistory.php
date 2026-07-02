<?php

namespace App\Core\Queue\Listeners;

use App\Core\Queue\Models\QueueJobHistory;
use Carbon\CarbonInterface;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;

class RecordQueueJobHistory
{
    public function onProcessing(JobProcessing $event): void
    {
        $payload = $event->job->payload();

        QueueJobHistory::query()->create([
            'job_uuid' => $payload['uuid'] ?? null,
            'job_class' => (string) ($payload['displayName'] ?? $payload['job'] ?? 'UnknownJob'),
            'queue' => (string) ($event->job->getQueue() ?? 'default'),
            'connection' => (string) ($event->connectionName ?? 'database'),
            'attempt' => (int) $event->job->attempts(),
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function onProcessed(JobProcessed $event): void
    {
        $payload = $event->job->payload();
        $finishedAt = now();
        $jobUuid = (string) ($payload['uuid'] ?? '');

        $history = $this->findLatestHistory($jobUuid);

        if (! $history) {
            QueueJobHistory::query()->create([
                'job_uuid' => $jobUuid !== '' ? $jobUuid : null,
                'job_class' => (string) ($payload['displayName'] ?? $payload['job'] ?? 'UnknownJob'),
                'queue' => (string) ($event->job->getQueue() ?? 'default'),
                'connection' => (string) ($event->connectionName ?? 'database'),
                'attempt' => (int) $event->job->attempts(),
                'status' => 'completed',
                'started_at' => $finishedAt,
                'finished_at' => $finishedAt,
                'duration_ms' => 0,
            ]);

            return;
        }

        $history->update([
            'status' => 'completed',
            'finished_at' => $finishedAt,
            'duration_ms' => $this->durationInMs($history->started_at, $finishedAt),
        ]);
    }

    public function onFailed(JobFailed $event): void
    {
        $payload = $event->job->payload();
        $finishedAt = now();
        $jobUuid = (string) ($payload['uuid'] ?? '');

        // Log the failure details for easier debugging
        try {
            Log::error('Queue job failed', [
                'job_uuid' => $jobUuid !== '' ? $jobUuid : null,
                'job_class' => (string) ($payload['displayName'] ?? $payload['job'] ?? 'UnknownJob'),
                'queue' => (string) ($event->job->getQueue() ?? 'default'),
                'connection' => (string) ($event->connectionName ?? 'database'),
                'attempt' => (int) $event->job->attempts(),
                'exception' => $event->exception->getMessage(),
                'trace' => $event->exception->getTraceAsString(),
            ]);
        } catch (\\Throwable $e) {
            // Swallow any logging errors to avoid interfering with failure handling
        }

        $history = $this->findLatestHistory($jobUuid);

        if (! $history) {
            QueueJobHistory::query()->create([
                'job_uuid' => $jobUuid !== '' ? $jobUuid : null,
                'job_class' => (string) ($payload['displayName'] ?? $payload['job'] ?? 'UnknownJob'),
                'queue' => (string) ($event->job->getQueue() ?? 'default'),
                'connection' => (string) ($event->connectionName ?? 'database'),
                'attempt' => (int) $event->job->attempts(),
                'status' => 'failed',
                'started_at' => $finishedAt,
                'finished_at' => $finishedAt,
                'duration_ms' => 0,
                'exception' => $event->exception->getMessage(),
            ]);

            return;
        }

        $history->update([
            'status' => 'failed',
            'finished_at' => $finishedAt,
            'duration_ms' => $this->durationInMs($history->started_at, $finishedAt),
            'exception' => $event->exception->getMessage(),
        ]);
    }

    private function findLatestHistory(string $jobUuid): ?QueueJobHistory
    {
        if ($jobUuid === '') {
            return null;
        }

        return QueueJobHistory::query()
            ->where('job_uuid', $jobUuid)
            ->where('status', 'running')
            ->latest('started_at')
            ->first();
    }

    private function durationInMs(CarbonInterface $startedAt, CarbonInterface $finishedAt): int
    {
        return (int) $startedAt->diffInMilliseconds($finishedAt);
    }
}
