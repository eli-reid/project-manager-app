<?php

namespace App\Core\Settings\Database\Seeders;

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Database\Seeder;

/**
 * Settings domain owns persistence/UI only.
 *
 * Defaults are sourced from:
 * - app/Core/{Module}/config/settings.php
 * - app/Domains/{Domain}/config/settings.php
 */
class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $model = new SettingsSqlite;
        $model->ensureSettingsDatabase();

        $pruneUndefined = (bool) config('settings-db.sync.prune_undefined_on_seed', true);
        $changes = app(DomainSettingsSynchronizer::class)->sync(pruneUndefined: $pruneUndefined);
        app(SettingsSqliteService::class)->clearAllCache();

        $this->command->info('✓ Settings synchronized from core/domain config providers: '.$changes.' changes'.($pruneUndefined ? ' (prune enabled)' : ''));
    }
}
