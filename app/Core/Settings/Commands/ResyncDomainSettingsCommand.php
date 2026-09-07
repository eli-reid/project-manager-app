<?php

namespace App\Core\Settings\Commands;

use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsDatabaseProvisioner;
use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Console\Command;

class ResyncDomainSettingsCommand extends Command
{
    protected $signature = 'settings:resync-domain
        {--dry-run : Preview discovered domain settings and groups without writing changes}
        {--prune : Delete database settings that are no longer defined by registered domains}
        {--force : Allow destructive prune mode in production}';

    protected $description = 'Resync registered domain settings and groups while preserving saved setting values';

    public function handle(
        SettingsDatabaseProvisioner $settingsDatabaseProvisioner,
        DomainSettingsSynchronizer $domainSettingsSynchronizer,
        SettingsSqliteService $settingsService,
    ): int {
        $prune = (bool) $this->option('prune');

        if ($prune && app()->environment('production') && ! (bool) $this->option('force')) {
            $this->error('Refusing to prune undefined settings in production without --force.');

            return self::FAILURE;
        }

        $settingsDatabaseProvisioner->ensureDatabase();

        $definitions = collect($domainSettingsSynchronizer->loadDefinitions());
        $groups = $definitions
            ->pluck('group')
            ->filter(fn (mixed $group): bool => is_string($group) && $group !== '')
            ->unique()
            ->sort()
            ->values();

        $this->info('Discovered settings: '.$definitions->count());
        $this->info('Discovered groups: '.$groups->count());

        if ($groups->isNotEmpty()) {
            $this->line('Groups: '.$groups->implode(', '));
        }

        if ((bool) $this->option('dry-run')) {
            $this->line('Dry run: database settings were not changed.');

            return self::SUCCESS;
        }

        $changes = $domainSettingsSynchronizer->sync(
            overwriteValues: false,
            pruneUndefined: $prune,
        );

        $settingsService->clearAllCache();

        $this->info('Domain settings resynced: '.$changes.' changes.');
        $this->line('Saved setting values were preserved; metadata and groups were refreshed from registered definitions.');

        return self::SUCCESS;
    }
}
