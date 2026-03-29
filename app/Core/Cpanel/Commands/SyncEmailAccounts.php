<?php

namespace App\Core\Cpanel\Commands;

use App\Core\Cpanel\Jobs\SyncEmailAccountsJob;
use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Console\Command;

class SyncEmailAccounts extends Command
{
    protected $signature = 'cpanel:sync-emails
        {--force : Run the sync immediately}
        {--queue : Dispatch the sync job to the queue}';

    protected $description = 'Synchronize cPanel mailbox accounts into the local cached_email_accounts table';

    public function handle(CpanelService $cpanelService): int
    {
        if (! $cpanelService->isConfigured()) {
            $this->error('cPanel configuration is incomplete. Please configure services.cpanel first.');

            return self::FAILURE;
        }

        if ($this->option('queue')) {
            SyncEmailAccountsJob::dispatch();

            $this->info('Email account sync has been queued.');

            return self::SUCCESS;
        }

        SyncEmailAccountsJob::dispatchSync();

        if ($this->option('force')) {
            $this->info('Email account sync completed (forced).');
        } else {
            $this->info('Email account sync completed.');
        }

        return self::SUCCESS;
    }
}
