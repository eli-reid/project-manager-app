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
@endphp

<div x-data="{ open: false }" class="pointer-events-none fixed inset-x-0 bottom-0 z-50">
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
            @if ($canViewStock)
                <a href="{{ route('stock-orders.mobile.index') }}" class="flex min-h-11 items-center rounded-2xl border border-zinc-800 bg-zinc-900 px-4 text-sm font-medium text-zinc-100" data-mobile-haptic>
                    {{ __('Stock Orders') }}
                </a>
            @endif

            @if ($canViewDocuments)
                <a href="{{ route('documents.mobile.index') }}" class="flex min-h-11 items-center rounded-2xl border border-zinc-800 bg-zinc-900 px-4 text-sm font-medium text-zinc-100" data-mobile-haptic>
                    {{ __('Documents') }}
                </a>
            @endif

            <button type="button" class="flex min-h-11 items-center rounded-2xl border border-zinc-800 bg-zinc-900 px-4 text-left text-sm font-medium text-zinc-100" onclick="window.triggerPWAInstall?.()" data-mobile-haptic>
                {{ __('Install App') }}
            </button>
        </div>
    </div>

    <nav class="pointer-events-auto mx-auto flex max-w-md items-center justify-between border-t border-zinc-800/90 bg-zinc-950/95 px-3 py-3 safe-area-bottom backdrop-blur">
        <a href="{{ $dashboardHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('dashboard') || request()->routeIs('mobile.dashboard') ? 'text-white' : 'text-zinc-500' }}" data-mobile-haptic>
            <span>{{ __('Home') }}</span>
        </a>

        @if ($canViewProjects)
            <a href="{{ $projectsHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('projects.*') ? 'text-white' : 'text-zinc-500' }}" data-mobile-haptic>
                <span>{{ __('Projects') }}</span>
            </a>
        @endif

        @if ($canViewTimecards)
            <a href="{{ $timecardsHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('timecards.*') ? 'text-white' : 'text-zinc-500' }}" data-mobile-haptic>
                <span>{{ __('Timecards') }}</span>
            </a>
        @endif

        @if ($canViewDailies)
            <a href="{{ $dailiesHref }}" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold {{ request()->routeIs('dailies.*') ? 'text-white' : 'text-zinc-500' }}" data-mobile-haptic>
                <span>{{ __('Dailies') }}</span>
            </a>
        @endif

        <button type="button" class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-[11px] font-semibold text-zinc-500" @click="open = true" data-mobile-haptic>
            <span>{{ __('More') }}</span>
        </button>
    </nav>
</div>