<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Contracts\SettingsProvider;
use Illuminate\Support\Str;

class SettingsClassDiscoverer
{
    /**
     * Discover classes implementing `SettingsProvider` based on configured paths.
     *
     * @return array<int, class-string<\App\Core\Settings\Contracts\SettingsProvider>>
     */
    public function discover(): array
    {
        $paths = config('settings.class_discover_paths', ['app/Core/*/Settings']);
        $found = [];

        foreach ((array) $paths as $pattern) {
            $glob = base_path($pattern);
            foreach ((array) glob($glob) as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }

                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
                foreach ($iterator as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }

                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $before = get_declared_classes();
                    try {
                        require_once $file->getPathname();
                    } catch (\Throwable $e) {
                        // ignore parse errors
                        continue;
                    }
                    $after = get_declared_classes();
                    $new = array_diff($after, $before);

                    foreach ($new as $fqcn) {
                        if (is_subclass_of($fqcn, SettingsProvider::class)) {
                            $found[] = $fqcn;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($found));
    }

    public function domainFromClass(string $class): string
    {
        $parts = explode('\\', ltrim($class, '\\'));
        // Expecting App\Core\{Domain}\Settings\...
        if (isset($parts[2]) && $parts[2] !== '') {
            return Str::lower($parts[2]);
        }

        return 'core';
    }
}
