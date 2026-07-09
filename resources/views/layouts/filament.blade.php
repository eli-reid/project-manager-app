<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Dashboard (Filament Preview)</title>
    {{-- Filament normally provides styles; keep placeholders so upgrade is simple --}}
    @stack('styles')
    <style>
        /* Minimal Filament-like wrapper styles for preview. Replace with Filament assets after install. */
        .filament-app { background: var(--filament-bg, #0f1724); color: var(--filament-text, #e5e7eb); min-height:100vh; }
        .filament-header { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .filament-main { padding: 1.25rem; }
        .filament-container { max-width: 1200px; margin: 0 auto; }
    </style>
    @livewireStyles
    @stack('head')
</head>
<body class="filament-app">
    <header class="filament-header">
        <div class="filament-container flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold">{{ config('app.name') }}</h1>
                <span class="text-sm text-zinc-400">Filament preview</span>
            </div>
            <nav>
                {{-- place for top nav / user menu --}}
            </nav>
        </div>
    </header>

    <main class="filament-main">
        <div class="filament-container">
            {{ $slot ?? $slot ?? null }}
        </div>
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
