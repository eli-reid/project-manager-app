<?php

namespace App\Core\Settings\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * SettingsCacheService
 *
 * Centralized caching layer for settings.
 * Sole responsibility: Remember, forget, and flush settings cache.
 */
class SettingsCacheService
{
    /**
     * Cache TTL in seconds (1 hour default)
     */
    protected int $ttl = 3600;

    /**
     * Cache key prefix
     */
    protected string $prefix = 'settings';

    public function __construct()
    {
        $this->ttl = config('settings-db.cache.ttl', 3600);
        $this->prefix = config('settings-db.cache.prefix', 'settings');
    }

    /**
     * Remember a value in cache
     */
    public function remember(string $key, callable $callback): mixed
    {
        if (! $this->isCacheAvailable()) {
            return call_user_func($callback);
        }

        $cacheKey = $this->getCacheKey($key);

        try {
            return Cache::remember($cacheKey, $this->ttl, $callback);
        } catch (\Exception $e) {
            // Fallback: execute callback without caching
            return call_user_func($callback);
        }
    }

    /**
     * Forget a cached value
     */
    public function forget(string $key): bool
    {
        if (! $this->isCacheAvailable()) {
            return true;
        }

        $cacheKey = $this->getCacheKey($key);

        try {
            return Cache::forget($cacheKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Forget all cache for a namespace
     */
    public function flushNamespace(string $namespace): bool
    {
        if (! $this->isCacheAvailable()) {
            return true;
        }

        try {
            // Cache::forget does not support wildcard patterns. Clear known keys and
            // then flush aggregate settings caches to guarantee consistency.
            $this->forgetMany([
                "settings.group.{$namespace}",
                "setting.{$namespace}",
                "setting.exists.{$namespace}",
            ]);

            return $this->flush();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Forget all settings cache
     */
    public function flush(): bool
    {
        if (! $this->isCacheAvailable()) {
            return true;
        }

        try {
            Cache::forget($this->prefix.'.all');
            Cache::forget($this->prefix.'.public');
            Cache::forget($this->prefix.'.all.grouped');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remember multiple values
     */
    public function rememberMany(array $keys, callable $callback): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->remember($key, function () use ($callback, $key) {
                return call_user_func($callback, $key);
            });
        }

        return $results;
    }

    /**
     * Forget multiple values
     */
    public function forgetMany(array $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (! $this->forget($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if cache is available
     */
    protected function isCacheAvailable(): bool
    {
        try {
            $enabled = config('settings-db.cache.enabled', true);

            if (! $enabled) {
                return false;
            }

            return app()->bound('cache') && Cache::getStore() !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $key): string
    {
        if (Str::startsWith($key, $this->prefix.'.')) {
            return $key;
        }

        return "{$this->prefix}.{$key}";
    }
}
