<?php

namespace App\Core\Settings\Console\Commands;

use App\Core\Settings\DTO\Setting;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsClassDiscoverer;
use Illuminate\Console\Command;

class SettingsSyncCommand extends Command
{
    protected $signature = 'settings:sync {--overwrite} {--prune} {--domains=*} {--dry-run} {--include-configs}';

    protected $description = 'Discover class-based settings and synchronize them into the settings database.';

    public function handle(SettingsClassDiscoverer $discoverer, DomainSettingsSynchronizer $synchronizer): int
    {
        $this->info('Discovering settings classes...');

        $classes = $discoverer->discover(function (string $directory): void {
            $this->info("Scanning directory: {$directory}");
        });
        $this->info('Discovered settings classes: '.count($classes));

        $allDefinitions = [];

        $domainsFilter = (array) $this->option('domains');

        foreach ($classes as $class) {
            $domain = $discoverer->domainFromClass($class);
            if (! empty($domainsFilter) && ! in_array($domain, $domainsFilter, true)) {
                continue;
            }

            $this->info("Loading definitions from {$class} (domain: {$domain})");

            try {
                $definitions = $class::definitions();
            } catch (\Throwable $e) {
                $this->error("Failed to load definitions from {$class}: {$e->getMessage()}");

                continue;
            }

            if (! is_array($definitions) || $definitions === []) {
                continue;
            }

            foreach ($definitions as $def) {
                if ($def instanceof Setting) {
                    $allDefinitions[] = $def;
                }
            }
        }

        $this->info('Discovered definitions: '.count($allDefinitions));

        if ($this->option('dry-run')) {
            $this->info('Dry run complete — no database mutations performed.');

            return 0;
        }

        $overwrite = (bool) $this->option('overwrite');
        $prune = (bool) $this->option('prune');

        $this->info('Synchronizing definitions into database...');

        $changes = $synchronizer->syncFromPayload($allDefinitions, $overwrite, $prune);

        $this->info("Synchronization complete. Changed: {$changes}");

        return 0;
    }
}
