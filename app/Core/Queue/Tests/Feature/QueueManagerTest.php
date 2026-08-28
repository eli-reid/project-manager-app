<?php

use App\Core\Identity\Models\User;
use App\Core\Queue\Listeners\RecordQueueJobHistory;
use App\Core\Queue\Models\FailedJob;
use App\Core\Queue\Models\QueueJob;
use App\Core\Queue\Models\QueueJobHistory;
use App\Core\Queue\Services\QueueManagerService;
use Illuminate\Contracts\Queue\Job as QueueContractJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('can load queue manager page for admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.queue.index'))
        ->assertOk()
        ->assertSee('Queue Manager');
});

it('tracks queue history on queue events', function (): void {
    QueueJobHistory::query()->create([
        'job_uuid' => 'job-123',
        'job_class' => 'App\\Jobs\\DemoJob',
        'queue' => 'default',
        'connection' => 'database',
        'attempt' => 1,
        'status' => 'running',
        'started_at' => now()->subSecond(),
    ]);

    $listener = app(RecordQueueJobHistory::class);

    $fakePayload = ['uuid' => 'job-123', 'displayName' => 'App\\Jobs\\DemoJob'];
    $fakeJob = Mockery::mock(QueueContractJob::class);
    $fakeJob->shouldReceive('payload')->andReturn($fakePayload);
    $fakeJob->shouldReceive('attempts')->andReturn(1);
    $fakeJob->shouldReceive('getQueue')->andReturn('default');

    $listener->onProcessed(new JobProcessed('database', $fakeJob));

    expect(QueueJobHistory::query()->where('job_uuid', 'job-123')->first()?->status)->toBe('completed');
});

it('queue manager service can delete failed job', function (): void {
    FailedJob::query()->create([
        'uuid' => 'f1f71edf-bebe-44f5-b713-6efe84f7090d',
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\DemoJob']),
        'exception' => 'boom',
        'failed_at' => now(),
    ]);

    app(QueueManagerService::class)->deleteFailedJob('f1f71edf-bebe-44f5-b713-6efe84f7090d');

    expect(FailedJob::query()->count())->toBe(0);
});

it('queue manager service can compute stats', function (): void {
    QueueJob::query()->create([
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\DemoJob']),
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    QueueJobHistory::query()->create([
        'job_uuid' => 'h1f71edf-bebe-44f5-b713-6efe84f7090d',
        'job_class' => 'App\\Jobs\\DemoJob',
        'queue' => 'default',
        'connection' => 'database',
        'attempt' => 1,
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
        'duration_ms' => 10,
    ]);

    $stats = app(QueueManagerService::class)->getStats();

    expect($stats['pending'])->toBe(1)
        ->and($stats['completed_today'])->toBe(1);
});

it('queue manager service calls retry commands', function (): void {
    Artisan::spy();

    app(QueueManagerService::class)->retryFailedJob('f1f71edf-bebe-44f5-b713-6efe84f7090d');
    app(QueueManagerService::class)->retryAllFailedJobs();
    app(QueueManagerService::class)->clearAllFailedJobs();

    Artisan::shouldHaveReceived('call')->with('queue:retry', ['id' => ['f1f71edf-bebe-44f5-b713-6efe84f7090d']])->once();
    Artisan::shouldHaveReceived('call')->with('queue:retry', ['id' => ['all']])->once();
    Artisan::shouldHaveReceived('call')->with('queue:flush')->once();
});

it('queue manager service can clear history by status filter', function (): void {
    QueueJobHistory::query()->create([
        'job_uuid' => 'history-running',
        'job_class' => 'App\\Jobs\\RunningJob',
        'queue' => 'default',
        'connection' => 'database',
        'attempt' => 1,
        'status' => 'running',
        'started_at' => now(),
    ]);

    QueueJobHistory::query()->create([
        'job_uuid' => 'history-completed',
        'job_class' => 'App\\Jobs\\CompletedJob',
        'queue' => 'default',
        'connection' => 'database',
        'attempt' => 1,
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
        'duration_ms' => 2,
    ]);

    QueueJobHistory::query()->create([
        'job_uuid' => 'history-failed',
        'job_class' => 'App\\Jobs\\FailedJob',
        'queue' => 'default',
        'connection' => 'database',
        'attempt' => 1,
        'status' => 'failed',
        'started_at' => now(),
        'finished_at' => now(),
        'duration_ms' => 4,
        'exception' => 'boom',
    ]);

    $deletedCompleted = app(QueueManagerService::class)->clearHistory('completed');

    expect($deletedCompleted)->toBe(1)
        ->and(QueueJobHistory::query()->where('status', 'completed')->count())->toBe(0)
        ->and(QueueJobHistory::query()->where('status', 'running')->count())->toBe(1)
        ->and(QueueJobHistory::query()->where('status', 'failed')->count())->toBe(1);

    $deletedAll = app(QueueManagerService::class)->clearHistory();

    expect($deletedAll)->toBe(2)
        ->and(QueueJobHistory::query()->count())->toBe(0);
});

it('logs failed job reasons', function (): void {
    Log::spy();

    $listener = app(RecordQueueJobHistory::class);

    $fakePayload = ['uuid' => 'job-log-123', 'displayName' => 'App\\Jobs\\DemoJob'];
    $fakeJob = Mockery::mock(QueueContractJob::class);
    $fakeJob->shouldReceive('payload')->andReturn($fakePayload);
    $fakeJob->shouldReceive('attempts')->andReturn(1);
    $fakeJob->shouldReceive('getQueue')->andReturn('default');

    $listener->onFailed(new \Illuminate\Queue\Events\JobFailed('database', $fakeJob, new \Exception('boom reason')));

    Log::shouldHaveReceived('error')->withArgs(function ($message, $context) {
        return is_string($message)
            && isset($context['exception'])
            && str_contains($context['exception'], 'boom reason')
            && isset($context['job_class'])
            && $context['job_class'] === 'App\\Jobs\\DemoJob';
    })->once();
});
