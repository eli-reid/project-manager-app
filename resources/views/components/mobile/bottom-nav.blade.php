@php
    $user = auth()->user();

    $dashboardHref = \Illuminate\Support\Facades\Route::has('mobile.dashboard') ? route('mobile.dashboard') : route('dashboard');
    $projectsHref = \Illuminate\Support\Facades\Route::has('projects.mobile.index') ? route('projects.mobile.index') : route('projects.index');
    $timecardsHref = \Illuminate\Support\Facades\Route::has('timecards.mobile.index') ? route('timecards.mobile.index') : route('timecards.index');
    $dailiesHref = \Illuminate\Support\Facades\Route::has('dailies.mobile.index') ? route('dailies.mobile.index') : route('dailies.index');

    $canViewProjects = $user?->can('viewAny', \App\Domains\Projects\Models\Project::class) ?? false;
    $canViewTimecards = $user?->can('viewAny', \App\Domains\Timecards\Models\Timecard::class) ?? false;
    $canViewDailies = $user?->can('viewAny', \App\Domains\Dailies\Models\DailyReport::class) ?? false;
    $canViewStock = $user?->can('viewAny', \App\Domains\Stock\Models\StockOrder::class) ?? false;
    $canViewDocuments = $user?->can('viewAny', \App\Domains\Documents\Models\Document::class) ?? false;
    $canCreateTimecards = $user?->can('create', \App\Domains\Timecards\Models\Timecard::class) ?? false;
    $newTimecardHref = \Illuminate\Support\Facades\Route::has('timecards.mobile.create') ? route('timecards.mobile.create') : route('timecards.create');
@endphp

<div
    x-data="{
        open: false,
        installable: false,
        isStandalone: false,
        isOffline: !navigator.onLine,
        init() {
            this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

            window.addEventListener('pwa-installable', () => {
                this.installable = true;
            });

            window.addEventListener('pwa-installed', () => {
                this.installable = false;
                this.isStandalone = true;
            });

            window.addEventListener('online', () => {
                this.isOffline = false;
            });

            window.addEventListener('offline', () => {
                this.isOffline = true;
            });
        },
    }"
    data-pwa-mobile-nav
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50"
>
    <div x-cloak x-show="open" class="pointer-events-auto absolute inset-0 -top-screen bg-black/60 backdrop-blur-sm" @click="open = false"></div>

    <div x-cloak x-show="open" x-transition.opacity x-transition.scale.origin.bottom class="pointer-events-auto absolute inset-x-4 bottom-24 rounded-3xl border border-zinc-800 bg-zinc-950 p-4 shadow-2xl">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-zinc-50">{{ __('More') }}</h2>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-zinc-800 bg-zinc-900 text-zinc-100" @click="open = false" data-mobile-haptic>
                <span class="sr-only">{{ __('Close') }}</span>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" /></svg>
            </button>
        </div>

        <div class="grid gap-2">
            @if ($canCreateTimecards)
                <a href="{{ $newTimecardHref }}" class="flex min-h-11 items-center gap-3 rounded-2xl border border-zinc-700 bg-zinc-900 px-4 text-sm font-semibold text-zinc-100" wire:navigate data-mobile-haptic>
                    <svg class="h-4 w-4 shrink-0 text-zinc-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                    {{ __('New Timecard') }}
                </a>
            @endif

            @if ($canViewStock)
                <a href="{{ route('stock-orders.mobile.index') }}" class="flex min-h-11 items-center rounded-2xl border border-zinc-800 bg-zinc-900 px-4 text-sm font-medium text-zinc-100" wire:navigate data-mobile-haptic>
                    {{ __('Stock Orders') }}
                </a>
            @endif

            @if ($canViewDocuments)
                <a href="{{ route('documents.mobile.global') }}" class="flex min-h-11 items-center rounded-2xl border border-zinc-800 bg-zinc-900 px-4 text-sm font-medium text-zinc-100" wire:navigate data-mobile-haptic>
                    {{ __('Documents') }}
                </a>
            @endif

            <button
                type="button"
                x-show="!isStandalone"
                class="flex min-h-11 items-center rounded-2xl border border-zinc-800 bg-zinc-900 px-4 text-left text-sm font-medium text-zinc-100"
                onclick="window.triggerPWAInstall?.()"
                data-pwa-install-action
                data-mobile-haptic
            >
                {{ __('Install App') }}
            </button>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button
                    type="submit"
                    class="flex min-h-11 w-full items-center rounded-2xl border border-zinc-800 bg-zinc-900 px-4 text-left text-sm font-medium text-zinc-100"
                    data-mobile-haptic
                >
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>

    <div x-cloak x-show="isOffline" class="pointer-events-auto mx-auto mb-2 w-fit rounded-full border border-amber-600/30 bg-amber-500/20 px-3 py-1 text-[11px] font-semibold text-amber-100">
        {{ __('Offline mode') }}
    </div>

    <nav class="pointer-events-auto mx-auto flex max-w-md items-center justify-between border-t border-zinc-800/90 bg-zinc-950/95 px-3 py-3 safe-area-bottom backdrop-blur">
        <a href="{{ $dashboardHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('dashboard') || request()->routeIs('mobile.dashboard') ? 'text-white' : 'text-zinc-500' }}" wire:navigate data-mobile-haptic>
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.707 1.293a1 1 0 0 0-1.414 0l-7 7A1 1 0 0 0 3 10h1v6a1 1 0 0 0 1 1h3.5a.5.5 0 0 0 .5-.5V13a1 1 0 0 1 1-1h0a1 1 0 0 1 1 1v3.5a.5.5 0 0 0 .5.5H15a1 1 0 0 0 1-1v-6h1a1 1 0 0 0 .707-1.707l-7-7Z" /></svg>
            <span>{{ __('Home') }}</span>
        </a>

        @if ($canViewProjects)
            <a href="{{ $projectsHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('projects.*') ? 'text-white' : 'text-zinc-500' }}" wire:navigate data-mobile-haptic>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4a2 2 0 0 1 2-2h2.5a1 1 0 0 1 .8.4l1.2 1.6H15a2 2 0 0 1 2 2v1H3V4Zm0 4h14v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8Z" /></svg>
                <span>{{ __('Projects') }}</span>
            </a>
        @endif

        @if ($canViewTimecards)
            <a href="{{ $timecardsHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('timecards.*') ? 'text-white' : 'text-zinc-500' }}" wire:navigate data-mobile-haptic>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H2V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 1-1Zm12 7H2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9Zm-4 2a1 1 0 1 1 0 2H6a1 1 0 1 1 0-2h8Z" /></svg>
                <span>{{ __('Timecards') }}</span>
            </a>
        @endif

        @if ($canViewDailies)
            <a href="{{ $dailiesHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('dailies.*') ? 'text-white' : 'text-zinc-500' }}" wire:navigate data-mobile-haptic>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7.414a2 2 0 0 0-.586-1.414l-3.414-3.414A2 2 0 0 0 11.586 2H5Zm5 2.5a.5.5 0 0 1 .5-.5h1.086a1 1 0 0 1 .707.293l2.414 2.414a1 1 0 0 1 .293.707V8.5a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.5-.5v-4Zm-2 7a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1Zm1 2.5a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2H9Z" /></svg>
                <span>{{ __('Dailies') }}</span>
            </a>
        @endif

        <button type="button" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold text-zinc-500" @click="open = true" data-mobile-haptic>
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M4 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM4 12a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm-12 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm6 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z" /></svg>
            <span>{{ __('More') }}</span>
        </button>
    </nav>
</div>