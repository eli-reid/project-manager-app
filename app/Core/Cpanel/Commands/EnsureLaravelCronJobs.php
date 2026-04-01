<?php

namespace App\Core\Cpanel\Commands;

use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Console\Command;

class EnsureLaravelCronJobs extends Command
{
    protected $signature = 'cpanel:ensure-laravel-cron
        {--project-path= : Absolute path to the Laravel app}
        {--php-binary= : PHP binary path used in cron commands}
        {--queue-connection=database : Queue connection passed to queue:work}
        {--queue=default : Queue names passed to queue:work}
        {--tries=3 : queue:work tries option}
        {--timeout=90 : queue:work timeout option}
        {--dry-run : Print commands without calling cPanel API}';

    protected $description = 'Ensure scheduler and queue cron jobs exist in cPanel';

    public function handle(CpanelService $cpanelService): int
    {
        if (! $cpanelService->isConfigured()) {
            $this->error('cPanel configuration is incomplete. Please configure services.cpanel first.');

            return self::FAILURE;
        }

        $projectPath = (string) ($this->option('project-path') ?: base_path());
        $phpBinary = (string) ($this->option('php-binary') ?: PHP_BINARY);
        $queueConnection = (string) $this->option('queue-connection');
        $queue = (string) $this->option('queue');
        $tries = (int) $this->option('tries');
        $timeout = (int) $this->option('timeout');

        $schedulerCommand = sprintf(
            'cd %s && %s artisan schedule:run >> /dev/null 2>&1',
            escapeshellarg($projectPath),
            escapeshellcmd($phpBinary),
        );

        $queueCommand = sprintf(
            'cd %s && %s artisan queue:work %s --queue=%s --stop-when-empty --tries=%d --timeout=%d >> /dev/null 2>&1',
            escapeshellarg($projectPath),
            escapeshellcmd($phpBinary),
            escapeshellarg($queueConnection),
            escapeshellarg($queue),
            $tries,
            $timeout,
        );

        $this->line('Scheduler command: '.$schedulerCommand);
        $this->line('Queue command: '.$queueCommand);

        if ((bool) $this->option('dry-run')) {
            $this->info('Dry run complete. No cPanel API calls were made.');

            return self::SUCCESS;
        }

        $schedulerResult = $cpanelService->ensureCronJob('*', '*', '*', '*', '*', $schedulerCommand);
        if (! ($schedulerResult['success'] ?? false)) {
            $this->error('Failed to ensure scheduler cron job: '.($schedulerResult['message'] ?? 'Unknown error'));

            return self::FAILURE;
        }

        $queueResult = $cpanelService->ensureCronJob('*', '*', '*', '*', '*', $queueCommand);
        if (! ($queueResult['success'] ?? false)) {
            $this->error('Failed to ensure queue cron job: '.($queueResult['message'] ?? 'Unknown error'));

            return self::FAILURE;
        }

        $this->info('Scheduler cron: '.($schedulerResult['action'] ?? 'updated'));
        $this->info('Queue cron: '.($queueResult['action'] ?? 'updated'));
        $this->info('cPanel cron jobs are configured.');

        return self::SUCCESS;
    }
}
