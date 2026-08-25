<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full">
    <head>
        <livewire:layouts.head :title="$title ?? null" />
    </head>
    <body class="min-h-safe-screen bg-zinc-950 text-zinc-50">
        <div class="min-h-safe-screen bg-zinc-950">
            <header class="sticky top-0 z-40 border-b border-zinc-800/80 bg-zinc-950/95 px-4 safe-area-top backdrop-blur">
                <div class="flex min-h-16 items-center gap-3 py-3">
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-zinc-800 bg-zinc-900 text-zinc-100"
                        data-fallback-url="{{ $mobileDashboardFallbackUrl ?? route('mobile.dashboard') }}"
                        onclick="handleBackNavigation(this.dataset.fallbackUrl)"
                        data-mobile-haptic
                        aria-label="{{ __('Go back') }}"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-500">{{ config('app.name') }}</p>
                        <h1 class="truncate text-lg font-semibold text-zinc-50">{{ $title ?? config('app.name') }}</h1>
                    </div>

                    @isset($headerAction)
                        <div class="shrink-0">
                            {{ $headerAction }}
                        </div>
                    @endisset
                </div>
            </header>

            <main class="pb-safe-offset">
                {{ $slot }}
            </main>

            <x-mobile.bottom-nav />
        </div>

        @fluxScripts
    </body>
</html>