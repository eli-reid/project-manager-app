<?php

it('keeps app resource blade views livewire first with explicit exceptions', function (): void {
    $allFiles = static function (string $path): array {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    };

    $projectRoot = str_replace('\\', '/', dirname(__DIR__, 4));
    $basePathPrefix = $projectRoot.'/';

    $resourceViewFiles = collect($allFiles($projectRoot.'/app'))
        ->map(function (string $path) use ($basePathPrefix): string {
            return str_replace('\\', '/', substr($path, strlen($basePathPrefix)));
        })
        ->filter(function (string $path): bool {
            return str_contains($path, '/Resources/Views/')
                && str_ends_with($path, '.blade.php');
        });

    $allowedNonLivewirePrefixes = [
        '/Resources/Views/auth/',
        '/Resources/Views/components/',
        '/Resources/Views/emails/',
        '/Resources/Views/layouts/',
        '/Resources/Views/partials/',
        '/Resources/Views/pdf/',
        '/Resources/Views/print/',
    ];

    $allowedLegacyNonLivewireFiles = [
        'app/Core/Announcement/Resources/Views/announcements/index.blade.php',
        'app/Core/Cpanel/Resources/Views/webmail/auto-login.blade.php',
        'app/Core/Settings/Resources/Views/admin/settings/import.blade.php',
        'app/Core/Settings/Resources/Views/admin/settings/index.blade.php',
        'app/Domains/ChangeOrders/Resources/Views/placeholder.blade.php',
        'app/Domains/Documents/Resources/Views/admin/scaffold.blade.php',
    ];

    $violations = $resourceViewFiles
        ->filter(function (string $path) use ($allowedLegacyNonLivewireFiles, $allowedNonLivewirePrefixes): bool {
            $normalizedPath = strtolower($path);

            if (str_contains($normalizedPath, '/resources/views/livewire/')) {
                return false;
            }

            if (in_array($path, $allowedLegacyNonLivewireFiles, true)) {
                return false;
            }

            foreach ($allowedNonLivewirePrefixes as $prefix) {
                if (str_contains($path, $prefix)) {
                    return false;
                }
            }

            return true;
        })
        ->sort()
        ->values();

    expect($violations->all())->toBe(
        [],
        'Non-Livewire app views must be explicit exceptions. Move new route-facing views under Resources/Views/livewire/. Violations: '.implode(', ', $violations->all())
    );
});

it('avoids direct blade rendering in route files outside explicit exceptions', function (): void {
    $allFiles = static function (string $path): array {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    };

    $projectRoot = str_replace('\\', '/', dirname(__DIR__, 4));
    $basePathPrefix = $projectRoot.'/';

    $routeFiles = collect($allFiles($projectRoot.'/routes'))
        ->merge(
            collect($allFiles($projectRoot.'/app'))
                ->filter(function (string $path): bool {
                    return str_contains(str_replace('\\', '/', $path), '/Routes/')
                        && str_ends_with($path, '.php');
                })
        )
        ->map(function (string $path) use ($basePathPrefix): string {
            return str_replace('\\', '/', substr($path, strlen($basePathPrefix)));
        })
        ->unique()
        ->values();

    $allowedRouteViewFiles = [
        'app/Domains/Invoices/Routes/web.php',
        'routes/web.php',
    ];

    $violations = $routeFiles
        ->filter(function (string $path) use ($allowedRouteViewFiles, $projectRoot): bool {
            if (in_array($path, $allowedRouteViewFiles, true)) {
                return false;
            }

            $source = file_get_contents($projectRoot.'/'.$path);

            if ($source === false) {
                return true;
            }

            return preg_match('/Route::view\s*\(|return\s+view\s*\(/', $source) === 1;
        })
        ->sort()
        ->values();

    expect($violations->all())->toBe(
        [],
        'Route files should dispatch Livewire components instead of returning Blade views. Violations: '.implode(', ', $violations->all())
    );
});

it('avoids direct blade rendering in controllers outside explicit legacy exceptions', function (): void {
    $allFiles = static function (string $path): array {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    };

    $projectRoot = str_replace('\\', '/', dirname(__DIR__, 4));
    $basePathPrefix = $projectRoot.'/';

    $controllerFiles = collect($allFiles($projectRoot.'/app'))
        ->filter(function (string $path): bool {
            return str_ends_with($path, 'Controller.php');
        })
        ->map(function (string $path) use ($basePathPrefix): string {
            return str_replace('\\', '/', substr($path, strlen($basePathPrefix)));
        })
        ->values();

    $allowedControllerViewFiles = [
        'app/Core/Announcement/Http/Controllers/AnnouncementFeedController.php',
        'app/Core/Settings/Http/Controllers/SettingsController.php',
    ];

    $violations = $controllerFiles
        ->filter(function (string $path) use ($allowedControllerViewFiles, $projectRoot): bool {
            if (in_array($path, $allowedControllerViewFiles, true)) {
                return false;
            }

            $source = file_get_contents($projectRoot.'/'.$path);

            if ($source === false) {
                return true;
            }

            return preg_match('/return\s+view\s*\(/', $source) === 1;
        })
        ->sort()
        ->values();

    expect($violations->all())->toBe(
        [],
        'Controllers should return Livewire route responses instead of Blade views. Violations: '.implode(', ', $violations->all())
    );
});
