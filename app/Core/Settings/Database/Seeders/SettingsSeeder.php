<?php

namespace App\Core\Settings\Database\Seeders;

use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsDatabaseProvisioner;
use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Database\Seeder;

/**
 * Settings domain owns persistence/UI only.
 *
 * Defaults are sourced from settings definitions explicitly registered by providers.
 */
class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(SettingsDatabaseProvisioner::class)->ensureDatabase();

        $pruneUndefined = (bool) config('settings-db.sync.prune_undefined_on_seed', true);
        $changes = app(DomainSettingsSynchronizer::class)->sync(pruneUndefined: $pruneUndefined);
        app(SettingsSqliteService::class)->clearAllCache();

        $this->command->info('✓ Settings synchronized from core/domain config providers: '.$changes.' changes'.($pruneUndefined ? ' (prune enabled)' : ''));
    }
}
