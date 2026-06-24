<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Services\SettingsClassDiscoverer;
use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\DTO\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
 

class DomainSettingsSynchronizer
{
    private const CACHE_KEY_HASH = 'settings.domain-definitions.hash';

    private const CACHE_KEY_NEXT_CHECK_AT = 'settings.domain-definitions.next-check-at';

    public function __construct(
        private readonly SettingsClassDiscoverer $discoverer
    ) {}

    /**
     * Sync settings when core/domain config definitions change.
     */
    public function syncIfChanged(): int
    {
        if (! $this->isCacheStoreReady()) {
            return 0;
        }

        $nextCheckAt = (int) Cache::get(self::CACHE_KEY_NEXT_CHECK_AT, 0);
        $now = time();

        if ($nextCheckAt > $now) {
            return 0;
        }

        $currentHash = $this->definitionsHash();
        $lastHash = (string) Cache::get(self::CACHE_KEY_HASH, '');

        if ($currentHash === $lastHash) {
            $this->cacheNextCheck($now);

            return 0;
        }

        $changes = $this->sync();

        Cache::forever(self::CACHE_KEY_HASH, $currentHash);
        $this->cacheNextCheck($now);

        return $changes;
    }

    protected function cacheNextCheck(int $now): void
    {
        $interval = (int) config('settings-db.sync.check_interval_seconds', 300);
        $interval = max(0, $interval);

        if ($interval > 0) {
            Cache::forever(self::CACHE_KEY_NEXT_CHECK_AT, $now + $interval);
        } else {
            Cache::forget(self::CACHE_KEY_NEXT_CHECK_AT);
        }
    }

    /**
     * Sync all discovered settings definitions into settings SQLite.
     */
    public function sync(bool $overwriteValues = false, bool $pruneUndefined = false): int
    {
        $settings = $this->loadDefinitions();
        $changedCount = 0;

        foreach ($settings as $settingDto) {
            if (! $settingDto instanceof Setting) {
                continue;
            }

            $definition = $this->toNormalizedArrayFromSetting($settingDto);

            $setting = SettingsSqlite::query()->where('key', $definition['key'])->first();

            if ($setting === null) {
                SettingsSqlite::query()->create($definition);
                $changedCount++;

                continue;
            }

            $value = $setting->value;
            $incomingValue = $definition['value'];

            $metadataUpdate = $definition;
            unset($metadataUpdate['value']);

            if ($overwriteValues || $value === null || $value === '') {
                $metadataUpdate['value'] = $incomingValue;
            }

            $setting->fill($metadataUpdate);

            if ($setting->isDirty()) {
                $setting->save();
                $changedCount++;
            }
        }

        $prunedCount = 0;
        if ($pruneUndefined) {
            $definitionKeys = collect($settings)
                ->pluck('key')
                ->filter(fn ($key) => is_string($key) && $key !== '')
                ->values()
                ->all();

            $prunedCount = $this->pruneUndefinedSettings($definitionKeys);
        }

        if (($changedCount + $prunedCount) > 0) {
            app(SettingsSqliteService::class)->clearAllCache();
        }

        return $changedCount + $prunedCount;
    }

    /**
     * @param  array<int, string>  $definitionKeys
     */
    protected function pruneUndefinedSettings(array $definitionKeys): int
    {
        if ($definitionKeys === []) {
            Log::warning('Skipping undefined settings prune because no settings definitions were discovered.');

            return 0;
        }

        return SettingsSqlite::query()
            ->whereNotIn('key', $definitionKeys)
            ->delete();
    }

    /**
     * Load settings definitions from discovered provider classes.
     *
     * Returns an array of `Setting` DTO instances. Providers may still
     * return Setting DTOs only.
     *
     * @return array<int, Setting>
     */
    public function loadDefinitions(): array
    {
        $settings = [];

        $classes = $this->discoverer->discover();

        foreach ($classes as $class) {
            try {
                $domain = $this->discoverer->domainFromClass($class);
                $defs = $class::definitions();
            } catch (\Throwable $e) {
                continue;
            }

            if (! is_array($defs) || $defs === []) {
                continue;
            }

            foreach ($defs as $definition) {
                // Only accept Setting DTO instances from providers.
                if ($definition instanceof Setting) {
                    $settings[] = $definition;
                }
            }
        }

        return $settings;
    }

    private function toNormalizedArrayFromSetting(Setting $s): array
    {
        $options = $s->options;

        if (is_array($options)) {
            $options = json_encode($options);
        }

        return [
            'key' => $s->key,
            'value' => $s->value,
            'default_value' => $s->value,
            'display_name' => $s->display_name ?? '',
            'description' => $s->description ?? '',
            'type' => $s->type->value,
            'group' => $s->group ?? '',
            'options' => $options,
            'order' => $s->order,
            'is_public' => $s->is_public,
            'is_visible' => $s->is_visible,
            'is_required' => $s->is_required,
            'encrypted' => $s->encrypted,
        ];
    }
    

    /**
     * Sync using an externally-provided set of normalized definitions.
     *
     * @param array<int, Setting> $definitions
     */
    public function syncFromPayload(array $definitions, bool $overwriteValues = false, bool $pruneUndefined = false): int
    {
        $changedCount = 0;

        foreach ($definitions as $settingDto) {
            if (! $settingDto instanceof Setting) {
                continue;
            }

            $definition = $this->toNormalizedArrayFromSetting($settingDto);

            $setting = SettingsSqlite::query()->where('key', $definition['key'])->first();

            if ($setting === null) {
                SettingsSqlite::query()->create($definition);
                $changedCount++;

                continue;
            }

            $value = $setting->value;
            $incomingValue = $definition['value'];

            $metadataUpdate = $definition;
            unset($metadataUpdate['value']);

            if ($overwriteValues || $value === null || $value === '') {
                $metadataUpdate['value'] = $incomingValue;
            }

            $setting->fill($metadataUpdate);

            if ($setting->isDirty()) {
                $setting->save();
                $changedCount++;
            }
        }

        $prunedCount = 0;
        if ($pruneUndefined) {
            $definitionKeys = collect($definitions)
                ->pluck('key')
                ->filter(fn ($key) => is_string($key) && $key !== '')
                ->values()
                ->all();

            $prunedCount = $this->pruneUndefinedSettings($definitionKeys);
        }

        if (($changedCount + $prunedCount) > 0) {
            app(SettingsSqliteService::class)->clearAllCache();
        }

        return $changedCount + $prunedCount;
    }

    protected function definitionsHash(): string
    {
        $settings = $this->loadDefinitions();
        $normalized = [];

        foreach ($settings as $s) {
            if ($s instanceof Setting) {
                $normalized[] = $this->toNormalizedArrayFromSetting($s);
            }
        }

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    

    protected function isCacheStoreReady(): bool
    {
        $defaultStore = (string) config('cache.default');

        if ($defaultStore !== 'database') {
            return true;
        }

        $cacheTable = (string) config('cache.stores.database.table', 'cache');

        return Schema::hasTable($cacheTable);
    }
}
