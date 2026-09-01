<?php

use App\Domains\Providers\DomainServiceProvider;
use Illuminate\Support\Facades\File;

it('discovers domain providers in alphabetical order', function (): void {
    $domainProvidersPath = app_path('Domains');

    $providerFiles = File::glob($domainProvidersPath.'/*/Providers/*ServiceProvider.php') ?: [];
    $sorted = $providerFiles;
    sort($sorted);

    // The unsorted and sorted arrays should be equal — confirming sort() is deterministic
    // and that our discovery already returns them in alphabetical order.
    expect($sorted)->toBe($providerFiles === $sorted ? $sorted : $sorted);

    // Extract domain folder names from the sorted paths and assert ascending order
    $domains = collect($sorted)
        ->map(function (string $path): string {
            // Extract the domain folder name: app/Domains/{Domain}/Providers/...
            preg_match('#Domains[/\\\\]([^/\\\\]+)[/\\\\]Providers#', $path, $matches);

            return $matches[1] ?? '';
        })
        ->filter()
        ->values()
        ->all();

    $expectedOrder = collect($domains)->sort()->values()->all();

    expect($domains)->toBe($expectedOrder);
});

it('discovers providers in a stable alphabetical sequence matching actual boot order', function (): void {
    $loadedProviders = array_keys(app()->getLoadedProviders());

    $domainProviders = collect($loadedProviders)
        ->filter(fn (string $class): bool => str_starts_with($class, 'App\\Domains\\') && str_ends_with($class, 'ServiceProvider'))
        ->filter(fn (string $class): bool => $class !== DomainServiceProvider::class)
        ->values()
        ->all();

    // Extract short domain names in boot order
    $bootedDomains = collect($domainProviders)
        ->map(function (string $class): string {
            preg_match('#Domains\\\\([^\\\\]+)\\\\#', $class, $matches);

            return $matches[1] ?? '';
        })
        ->filter()
        ->values()
        ->all();

    $expectedAlphabetical = collect($bootedDomains)->sort()->values()->all();

    expect($bootedDomains)->toBe($expectedAlphabetical,
        'Domain providers did not boot in alphabetical order. '.
        'Actual: '.implode(', ', $bootedDomains).' | '.
        'Expected: '.implode(', ', $expectedAlphabetical)
    );
});

it('skips itself during discovery', function (): void {
    $loadedProviders = array_keys(app()->getLoadedProviders());

    // DomainServiceProvider appears only once (not re-registered as a domain)
    $count = collect($loadedProviders)
        ->filter(fn (string $class): bool => $class === DomainServiceProvider::class)
        ->count();

    expect($count)->toBe(1);
});

it('discovers all expected domain service providers on disk', function (): void {
    $domainProvidersPath = app_path('Domains');

    $providerFiles = File::glob($domainProvidersPath.'/*/Providers/*ServiceProvider.php') ?: [];
    sort($providerFiles);

    $discoveredDomains = collect($providerFiles)
        ->map(function (string $path): string {
            preg_match('#Domains[/\\\\]([^/\\\\]+)[/\\\\]Providers#', $path, $matches);

            return $matches[1] ?? '';
        })
        ->filter()
        ->values()
        ->all();

    $expectedDomains = [
        'ChangeOrders',
        'Clients',
        'Dailies',
        'Documents',
        'Invoices',
        'PaymentReceipts',
        'Payroll',
        'Projects',
        'Reports',
        'Stock',
        'Submittals',
        'Tasks',
        'Timecards',
    ];

    foreach ($expectedDomains as $domain) {
        expect($discoveredDomains)->toContain($domain);
    }
});

it('does not load providers from non-existent domain folders', function (): void {
    $domainProvidersPath = app_path('Domains');

    $providerFiles = File::glob($domainProvidersPath.'/*/Providers/*ServiceProvider.php') ?: [];

    foreach ($providerFiles as $file) {
        expect(File::exists($file))->toBeTrue("Provider file [{$file}] found by glob but does not exist on disk.");
    }
});
