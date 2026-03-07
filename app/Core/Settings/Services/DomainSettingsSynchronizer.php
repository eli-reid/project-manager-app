<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Contracts\DomainSettingsProvider;
use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DomainSettingsSynchronizer
{
    private const CACHE_KEY_HASH = 'settings.domain-definitions.hash';

    private const CACHE_KEY_NEXT_CHECK_AT = 'settings.domain-definitions.next-check-at';

    /**
     * Sync settings when core/domain config definitions change.
     */
    public function syncIfChanged(): int
    {
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
        $definitions = $this->loadDefinitions();
        $changedCount = 0;

        foreach ($definitions as $definition) {
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
     * @return array<int, array<string, mixed>>
     */
    public function loadDefinitions(): array
    {
        $definitions = [];

        foreach ($this->settingsConfigFiles() as $configFile) {
            $domain = $this->domainNameFromPath($configFile);
            $payload = require $configFile;
            $domainDefinitions = $this->resolvePayload($payload, $domain);

            foreach ($domainDefinitions as $definition) {
                $normalized = $this->normalizeDefinition($definition, $domain);

                if ($normalized !== null) {
                    $definitions[] = $normalized;
                }
            }
        }

        return $definitions;
    }

    /**
     * @return array<int, string>
     */
    protected function settingsConfigFiles(): array
    {
        $files = [
            ...(file_exists(app_path('config/settings.php')) ? [app_path('config/settings.php')] : []),
            ...(glob(app_path('Core/*/config/settings.php')) ?: []),
            ...(glob(app_path('Domains/*/config/settings.php')) ?: []),
        ];

        sort($files);

        return $files;
    }

    protected function definitionsHash(): string
    {
        $segments = [];

        foreach ($this->settingsConfigFiles() as $filePath) {
            $mtime = @filemtime($filePath) ?: 0;
            $segments[] = $filePath.'|'.$mtime;
        }

        return hash('sha256', implode(';', $segments));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolvePayload(mixed $payload, string $domain): array
    {
        if (is_string($payload) && class_exists($payload) && is_subclass_of($payload, DomainSettingsProvider::class)) {
            /** @var class-string<DomainSettingsProvider> $payload */
            return $payload::settings();
        }

        if (is_array($payload) && isset($payload['settings']) && is_array($payload['settings'])) {
            return $payload['settings'];
        }

        if (is_array($payload)) {
            return $payload;
        }

        Log::warning('Invalid domain settings payload.', [
            'domain' => $domain,
            'payload_type' => gettype($payload),
        ]);

        return [];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>|null
     */
    protected function normalizeDefinition(array $definition, string $domain): ?array
    {
        $key = trim((string) ($definition['key'] ?? ''));

        if ($key === '') {
            return null;
        }

        $defaultValue = $definition['default_value'] ?? $definition['value'] ?? null;
        $displayName = (string) ($definition['display_name'] ?? Str::of($key)->afterLast('.')->replace(['_', '-'], ' ')->headline());
        $group = (string) ($definition['group'] ?? Str::lower($domain));

        $options = $definition['options'] ?? null;

        if (is_array($options)) {
            $options = json_encode($options);
        }

        return [
            'key' => $key,
            'value' => $definition['value'] ?? $defaultValue,
            'default_value' => $defaultValue,
            'display_name' => $displayName,
            'description' => (string) ($definition['description'] ?? ''),
            'type' => (string) ($definition['type'] ?? 'text'),
            'group' => $group,
            'options' => $options,
            'order' => (int) ($definition['order'] ?? 0),
            'is_public' => (bool) ($definition['is_public'] ?? false),
            'is_visible' => (bool) ($definition['is_visible'] ?? true),
            'is_required' => (bool) ($definition['is_required'] ?? false),
            'encrypted' => (bool) ($definition['encrypted'] ?? false),
        ];
    }

    protected function domainNameFromPath(string $path): string
    {
        return basename(dirname(dirname($path)));
    }
}
