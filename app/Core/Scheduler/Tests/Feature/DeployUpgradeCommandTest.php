<?php

it('runs deployment and upgrade steps in order by default', function (): void {
    $this->artisan('app:deploy-upgrade --dry-run')
        ->expectsOutputToContain('[dry-run] Clear optimization caches (optimize:clear)')
        ->expectsOutputToContain('[dry-run] Run database migrations (migrate)')
        ->expectsOutputToContain('[dry-run] Sync scheduler task definitions (scheduler:sync-tasks)')
        ->expectsOutputToContain('[dry-run] Rebuild optimization caches (optimize)')
        ->expectsOutputToContain('[dry-run] Restart queue workers (queue:restart)')
        ->assertSuccessful();
});

it('supports skip options for migrations scheduler sync optimize and queue restart', function (): void {
    $this->artisan('app:deploy-upgrade --dry-run --skip-migrate --skip-scheduler-sync --no-optimize --skip-queue-restart')
        ->expectsOutputToContain('[dry-run] Clear optimization caches (optimize:clear)')
        ->doesntExpectOutputToContain('Run database migrations')
        ->doesntExpectOutputToContain('Sync scheduler task definitions')
        ->doesntExpectOutputToContain('Rebuild optimization caches')
        ->doesntExpectOutputToContain('Restart queue workers')
        ->assertSuccessful();
});
