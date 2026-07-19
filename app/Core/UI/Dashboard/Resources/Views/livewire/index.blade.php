<div class="min-h-screen bg-transparent text-zinc-900 dark:text-zinc-100">
    <header class="fixed inset-x-0 top-0 z-40 border-b border-zinc-200/70 bg-white/80 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/70">
        <div class="flex items-center justify-between pl-2 pr-2 px-1 py-1">
            <div class="min-w-0 flex">
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-zinc-950 dark:text-white">{{ $siteName }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:justify-end">
                <label class="hidden w-full max-w-xs items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white/70 px-3 py-2 text-sm text-zinc-500 shadow-sm sm:flex dark:border-white/10 dark:bg-white/5 dark:text-zinc-400 dark:shadow-none">
                    <input type="text" placeholder="Search panels" class="w-full border-0 bg-transparent p-0 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100 dark:placeholder:text-zinc-500" />
                </label>
                <div class="rounded-2xl border border-zinc-200/80 bg-white/70 shadow-sm dark:border-white/10 dark:bg-white/5 dark:shadow-none">
                    <livewire:auth.user.desktop-user-menu />
                </div>
            </div>
        </div>
    </header>

    <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-zinc-200/70 bg-white/78 px-5 py-6 pt-28 backdrop-blur xl:flex xl:flex-col xl:overflow-y-auto dark:border-white/10 dark:bg-zinc-950/65">
        @livewire(App\Core\UI\Navigation\Livewire\NavMenu::class, ['variant' => 'dashboard'], key('dashboard-sidebar-navigation'))
    </aside>

    <main class="left-80 min-w-0 px-4 pb-4 pt-28 sm:px-8 lg:px-8 xl:pl-80">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <section class="rounded-4xl border border-zinc-200/70 bg-white/72 p-4 shadow-xl shadow-slate-200/60 backdrop-blur sm:p-6 dark:border-white/10 dark:bg-zinc-950/45 dark:shadow-black/20">
                @if ($currentPanel)
                    @livewire($currentPanel['component'], [], 'dashboard-panel-'.$currentPanel['key'])
                @else
                    <div class="flex min-h-72 items-center justify-center rounded-3xl border border-dashed border-zinc-300/80 bg-white/60 p-8 text-sm text-zinc-500 dark:border-white/10 dark:bg-white/3 dark:text-zinc-400">
                        No dashboard panels are available yet.
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>