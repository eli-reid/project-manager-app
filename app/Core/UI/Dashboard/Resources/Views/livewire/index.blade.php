<div class="flex min-h-screen bg-transparent text-zinc-100">
    <aside class="hidden w-72 shrink-0 border-r border-white/10 bg-zinc-950/65 px-5 py-6 backdrop-blur xl:flex xl:flex-col">
        <div class="flex items-center gap-3 border-b border-white/10 pb-5">
            <div class="flex size-11 items-center justify-center overflow-hidden rounded-2xl bg-white/5 ring-1 ring-white/10">
                <img src="/ms-icon 1.png" alt="{{ config('app.name') }}" class="size-9 object-cover" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold tracking-[0.28em] text-zinc-400">MIDSTATE</p>
                <h1 class="truncate text-base font-semibold text-white">{{ config('app.name') }}</h1>
            </div>
        </div>

        <div class="mt-6 flex-1 space-y-2">
            <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.3em] text-zinc-500">Panels</p>

            @foreach ($panels as $panel)
                <a
                    href="{{ route('dashboard', ['panel' => $panel['key']]) }}"
                    wire:navigate
                    data-test="dashboard-panel-{{ $panel['key'] }}-link"
                    class="group flex items-start gap-3 rounded-2xl px-3 py-3 transition {{ $activePanel === $panel['key']
                        ? 'bg-amber-500/15 text-white ring-1 ring-amber-400/30'
                        : 'text-zinc-400 hover:bg-white/5 hover:text-zinc-100' }}"
                >
                    <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-xl {{ $activePanel === $panel['key'] ? 'bg-amber-400/20 text-amber-300' : 'bg-white/5 text-zinc-500 group-hover:text-zinc-300' }}">
                        {{ strtoupper(substr($panel['label'], 0, 1)) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{ $panel['label'] }}</span>
                        @if ($panel['description'] !== '')
                            <span class="mt-1 block text-xs leading-5 text-zinc-500 group-hover:text-zinc-400">{{ $panel['description'] }}</span>
                        @endif
                    </span>
                    @if ($panel['badge'] !== '')
                        <span class="rounded-full border border-white/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-zinc-300">{{ $panel['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </aside>

    <main class="min-w-0 flex-1 px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <header class="flex flex-col gap-4 rounded-[1.75rem] border border-white/10 bg-zinc-950/55 px-5 py-4 shadow-2xl shadow-black/20 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-sky-300/80">Dashboard Workspace</p>
                    <div class="mt-1 flex items-center gap-3">
                        <h2 class="truncate text-2xl font-semibold text-white">{{ $currentPanel['label'] ?? 'Dashboard' }}</h2>
                        @if (($currentPanel['badge'] ?? '') !== '')
                            <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-300">{{ $currentPanel['badge'] }}</span>
                        @endif
                    </div>
                    @if (($currentPanel['description'] ?? '') !== '')
                        <p class="mt-1 max-w-2xl text-sm text-zinc-400">{{ $currentPanel['description'] }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-3 sm:justify-end">
                    <label class="hidden w-full max-w-xs items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-zinc-400 sm:flex">
                        <span class="text-zinc-500">Search</span>
                        <input type="text" placeholder="Search panels" class="w-full border-0 bg-transparent p-0 text-sm text-zinc-100 placeholder:text-zinc-500 focus:outline-none focus:ring-0" />
                    </label>
                    <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-2.5">
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-[0.24em] text-zinc-500">Signed in</p>
                            <p class="max-w-[9rem] truncate text-sm font-medium text-white">{{ $displayName }}</p>
                        </div>
                        <div class="inline-flex size-10 items-center justify-center rounded-2xl bg-sky-400/20 text-sm font-semibold text-sky-200 ring-1 ring-sky-300/20">
                            {{ $displayInitials }}
                        </div>
                    </div>
                </div>
            </header>

            <section class="rounded-[2rem] border border-white/10 bg-zinc-950/45 p-4 shadow-2xl shadow-black/20 backdrop-blur sm:p-6">
                @if ($currentPanel)
                    @livewire($currentPanel['component'], [], 'dashboard-panel-'.$currentPanel['key'])
                @else
                    <div class="flex min-h-[18rem] items-center justify-center rounded-[1.5rem] border border-dashed border-white/10 bg-white/[0.03] p-8 text-sm text-zinc-400">
                        No dashboard panels are available yet.
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>