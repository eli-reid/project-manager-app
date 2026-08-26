<?php

declare(strict_types=1);

it('prevents raw document queries outside documents domain livewire consumers', function (): void {
    $boundedDomainPaths = [
        app_path('Domains/Projects/Livewire'),
        app_path('Domains/Submittals/Livewire'),
        app_path('Domains/ChangeOrders/Livewire'),
        app_path('Domains/RFIs/Livewire'),
    ];

    $violations = [];

    foreach ($boundedDomainPaths as $path) {
        if (! is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($fileInfo->getPathname());
            if (! is_string($contents)) {
                continue;
            }

            if (str_contains($contents, 'Document::query(')) {
                $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $fileInfo->getPathname());
            }
        }
    }

    expect($violations)->toBe([]);
});
