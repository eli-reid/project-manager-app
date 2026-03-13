<?php

namespace App\Core\Scheduler\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DeployUpgradeCommand extends Command
{
    protected $signature = 'app:deploy-upgrade
        {--force : Force destructive operations in production}
        {--dry-run : Print the orchestration plan without executing commands}
        {--skip-migrate : Skip running database migrations}
        {--seed : Run database seeders after migrations}
        {--skip-scheduler-sync : Skip syncing scheduler task definitions}
        {--no-optimize : Skip optimize step}
        {--skip-queue-restart : Skip queue worker restart}
        {--publish-assets : Publish Laravel assets during upgrades}';

    protected $description = 'Orchestrate deployment and upgrade steps for the application';

    public function handle(): int
    {
        if (app()->environment('production') && ! (bool) $this->option('force')) {
            $this->error('Refusing to run in production without --force.');

            return self::FAILURE;
        }

        $this->info('Starting deployment and upgrade orchestration...');

        if (! $this->runStep('Clear optimization caches', 'optimize:clear')) {
            return self::FAILURE;
        }

        if (! (bool) $this->option('skip-migrate')) {
            if (! $this->runStep('Run database migrations', 'migrate', ['--force' => true])) {
                return self::FAILURE;
            }

            if ((bool) $this->option('seed')) {
                if (! $this->runStep('Run database seeders', 'db:seed', ['--force' => true])) {
                    return self::FAILURE;
                }
            }
        }

        if (! (bool) $this->option('skip-scheduler-sync')) {
            if (! $this->runStep('Sync scheduler task definitions', 'scheduler:sync-tasks')) {
                return self::FAILURE;
            }
        }

        if ((bool) $this->option('publish-assets')) {
            if (! $this->runStep('Publish Laravel assets', 'vendor:publish', [
                '--tag' => 'laravel-assets',
                '--force' => true,
            ])) {
                return self::FAILURE;
            }
        }

        if (! (bool) $this->option('no-optimize')) {
            if (! $this->runStep('Rebuild optimization caches', 'optimize')) {
                return self::FAILURE;
            }
        }

        if (! (bool) $this->option('skip-queue-restart')) {
            if (! $this->runStep('Restart queue workers', 'queue:restart')) {
                return self::FAILURE;
            }
        }

        $this->info('Deployment and upgrade orchestration completed successfully.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function runStep(string $label, string $command, array $arguments = []): bool
    {
        if ((bool) $this->option('dry-run')) {
            $this->line("- [dry-run] {$label} ({$command})");

            return true;
        }

        $this->line("- {$label}");

        $exitCode = Artisan::call($command, $arguments);

        if ($exitCode !== self::SUCCESS) {
            $this->error("Step failed: {$label} ({$command})");

            return false;
        }

        return true;
    }
}
