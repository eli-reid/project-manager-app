<?php

namespace App\Core\Scheduler\Commands;

use App\Core\Scheduler\Services\TaskDefinitionSyncService;
use Illuminate\Console\Command;

class SyncSchedulerTasksCommand extends Command
{
    protected $signature = 'scheduler:sync-tasks';

    protected $description = 'Sync registered domain task definitions into the available_tasks table';

    public function handle(TaskDefinitionSyncService $sync): int
    {
        $changes = $sync->sync();

        if ($changes > 0) {
            $this->info("Synced {$changes} task definition(s).");
        } else {
            $this->info('Task definitions are already up to date.');
        }

        return self::SUCCESS;
    }
}
