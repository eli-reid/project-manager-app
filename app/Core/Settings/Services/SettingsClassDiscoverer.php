<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use FilesystemIterator;
use Illuminate\Support\Str;

class SettingsClassDiscoverer
{
    /**
     * Discover classes implementing `SettingsRegistryContract` based on configured paths.
     *
     * @return array<int, class-string<SettingsRegistryContract>>
     */
    public function discover(?callable $directoryReporter = null): array
    {
        $paths = $this->discoveryPaths();
        $found = [];
        foreach ($paths as $pattern) {
            foreach ($this->directoriesForPattern($pattern) as $directory) {
                if ($directoryReporter !== null) {
                    $directoryReporter($directory);
                }

                foreach ($this->phpFilesInDirectory($directory) as $file) {
                    $before = get_declared_classes();

                    try {
                        require_once $file->getPathname();
                    } catch (\Throwable) {
                        continue;
                    }

                    $after = get_declared_classes();
                    $new = array_diff($after, $before);

                    foreach ($new as $fqcn) {
                        if (is_subclass_of($fqcn, SettingsRegistryContract::class)) {
                            $found[] = $fqcn;
                        }
                    }
                }
            }
        }
        return array_values(array_unique($found));
    }

    /**
     * @return array<int, string>
     */
    private function discoveryPaths(): array
    {
        $paths = config('settings.class_discover_paths');

        if (! is_array($paths) || $paths === []) {
            return [
                'app/Core/*/Settings',
                'app/Domains/*/Settings',
                'app/PlugIns/*/Settings',
            ];
        }

        return array_values(array_filter(
            array_map(fn (string $path): string => $this->normalizeDiscoveryPath($path), array_filter($paths, static fn ($path): bool => is_string($path) && $path !== '')),
            static fn (string $path): bool => $path !== ''
        ));
    }

    private function normalizeDiscoveryPath(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', trim($path));

        if ($normalizedPath === '') {
            return '';
        }

        if (str_starts_with($normalizedPath, 'app/')) {
            return $normalizedPath;
        }

        return 'app/'.ltrim($normalizedPath, '/');
    }

    /**
     * @return array<int, string>
     */
    private function directoriesForPattern(string $pattern): array
    {
        $directories = glob(base_path($pattern)) ?: [];

        return array_values(array_filter($directories, static fn (string $directory): bool => is_dir($directory)));
    }

    /**
     * @return iterable<int, \SplFileInfo>
     */
    private function phpFilesInDirectory(string $directory): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
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
