<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <livewire:layouts.head :title="$title ?? null" />
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <livewire:layouts.app-sidebar />

        <flux:main class="min-w-0 p-0 lg:p-0">
            @if (isset($domainNavbar) && (method_exists($domainNavbar, 'isEmpty') ? ! $domainNavbar->isEmpty() : trim((string) $domainNavbar) !== ''))
                <div class="sticky top-0 z-30 border-b border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                    {{ $domainNavbar }}
                </div>
            @endif

            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
