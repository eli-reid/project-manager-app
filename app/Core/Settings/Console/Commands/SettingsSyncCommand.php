<?php

namespace App\Core\Settings\Console\Commands;

use App\Core\Settings\Services\SettingsClassDiscoverer;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\DTO\Setting;
use Illuminate\Console\Command;

class SettingsSyncCommand extends Command
{
    protected $signature = 'settings:sync {--overwrite} {--prune} {--domains=*} {--dry-run} {--include-configs}';

    protected $description = 'Discover class-based settings and synchronize them into the settings database.';

    public function handle(SettingsClassDiscoverer $discoverer, DomainSettingsSynchronizer $synchronizer): int
    {
        $this->info('Discovering settings classes...');

        $classes = $discoverer->discover();

        $allDefinitions = [];

        // Optionally include legacy config files from domains — only accept Setting DTOs
        if ($this->option('include-configs')) {
            $this->info('Including legacy domain config/settings.php files...');
            $paths = config('settings.class_discover_paths', ['app/Core/*/Settings']);

            foreach ((array) $paths as $pattern) {
                $glob = base_path($pattern);

                foreach ((array) glob($glob) as $dir) {
                    if (! is_dir($dir)) {
                        continue;
                    }

                    // Expect domain at app/Core/{Domain}/...
                    $domainDir = dirname($dir);
                    $domain = basename($domainDir);
                    $file = $domainDir.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'settings.php';

                    if (! is_file($file)) {
                        continue;
                    }

                    $this->info("Loading config file for domain: {$domain}");

                    try {
                        $payload = require $file;
                    } catch (\Throwable $e) {
                        $this->error("Failed to load {$file}: {$e->getMessage()}");
                        continue;
                    }

                    if (is_array($payload) && $payload !== []) {
                        foreach ($payload as $entry) {
                            if ($entry instanceof Setting) {
                                $allDefinitions[] = $entry;
                            }
                        }
                    }
                }
            }
        }

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
