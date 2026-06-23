<?php

namespace App\Core\Settings\Console\Commands;

use App\Core\Settings\Services\SettingsClassDiscoverer;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use Illuminate\Console\Command;

class SettingsSyncCommand extends Command
{
    protected $signature = 'settings:sync {--overwrite} {--prune} {--domains=*} {--dry-run} {--include-configs}';

    protected $description = 'Discover class-based settings and synchronize them into the settings database.';

    public function handle(SettingsClassDiscoverer $discoverer, SettingsRegistryContract $registry, DomainSettingsSynchronizer $synchronizer): int
    {
        $this->info('Discovering settings classes...');

        $classes = $discoverer->discover();

        // Optionally include legacy config files from domains
        if ($this->option('include-configs')) {
            $this->info('Including legacy domain config/settings.php files...');
            $pattern = base_path('app/Core/*/config/settings.php');
            foreach ((array) glob($pattern) as $file) {
                if (! is_file($file)) {
                    continue;
                }

                $domain = basename(dirname(dirname($file)));
                $this->info("Loading config file for domain: {$domain}");

                try {
                    $payload = require $file;
                } catch (\Throwable $e) {
                    $this->error("Failed to load {$file}: {$e->getMessage()}");
                    continue;
                }

                if (is_array($payload) && $payload !== []) {
                    // Normalize payload entries: convert DTOs to arrays when present
                    $normalized = [];
                    foreach ($payload as $entry) {
                        if (is_object($entry) && method_exists($entry, 'toArray')) {
                            $normalized[] = $entry->toArray();
                            continue;
                        }

                        if (is_object($entry)) {
                            // basic best-effort mapping
                            $normalized[] = [
                                'key' => $entry->key ?? null,
                                'value' => $entry->value ?? null,
                                'default_value' => $entry->value ?? null,
                                'display_name' => $entry->display_name ?? null,
                                'description' => $entry->description ?? null,
                                'type' => is_object($entry->type) ? $entry->type->value : (string) $entry->type,
                                'form_field_type' => is_object($entry->formFieldType) ? $entry->formFieldType->value : (string) ($entry->formFieldType ?? $entry->type ?? 'text'),
                                'group' => $entry->group ?? null,
                                'options' => $entry->options ?? null,
                                'order' => $entry->order ?? 100,
                                'is_public' => $entry->is_public ?? false,
                                'is_visible' => $entry->is_visible ?? true,
                                'is_required' => $entry->is_required ?? false,
                                'encrypted' => $entry->encrypted ?? false,
                            ];
                            continue;
                        }

                        $normalized[] = $entry;
                    }

                    $registry->registerDefinitions($domain, $normalized);
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

            // If definitions are Setting DTOs, convert to array shape expected by registry
            $payload = [];
            foreach ($definitions as $def) {
                if (is_object($def) && method_exists($def, 'toArray')) {
                    $payload[] = $def->toArray();
                    continue;
                }

                if (is_object($def)) {
                    // Fallback mapping for objects without toArray()
                    $payload[] = [
                        'key' => $def->key ?? null,
                        'value' => $def->value ?? null,
                        'default_value' => $def->value ?? null,
                        'display_name' => $def->display_name ?? null,
                        'description' => $def->description ?? null,
                        'type' => is_object($def->type) ? $def->type->value : (string) $def->type,
                        'form_field_type' => is_object($def->formFieldType) ? $def->formFieldType->value : (string) $def->formFieldType,
                        'group' => $def->group ?? null,
                        'options' => $def->options ?? null,
                        'order' => $def->order ?? 100,
                        'is_public' => $def->is_public ?? false,
                        'is_visible' => $def->is_visible ?? true,
                        'is_required' => $def->is_required ?? false,
                        'encrypted' => $def->encrypted ?? false,
                    ];
                } else {
                    $payload[] = $def;
                }
            }

            $registry->registerDefinitions($domain, $payload);
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run complete — no database mutations performed.');
            return 0;
        }

        $overwrite = (bool) $this->option('overwrite');
        $prune = (bool) $this->option('prune');

        $this->info('Synchronizing definitions into database...');

        $changes = $synchronizer->sync($overwrite, $prune);

        $this->info("Synchronization complete. Changed: {$changes}");

        return 0;
    }
}
