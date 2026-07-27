<div class="w-full space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Plugin System</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Inventory installed plugins, stage future marketplace installs, and keep third-party code behind an explicit security review gate.</p>
        </div>
        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
            Marketplace installation is scaffolded as a staged review flow. Activation remains blocked until the review workflow is implemented.
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Security Gate</p>
            <h2 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Primary Checkpoint</h2>
            <ul class="mt-3 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                <li>Checksums and publisher signatures are required for staged marketplace plugins.</li>
                <li>Critical capabilities such as runtime code evaluation are quarantined immediately.</li>
                <li>Wildcard permissions are blocked before a plugin can move past intake.</li>
            </ul>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Installed Records</p>
            <h2 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $installedPlugins->count() }}</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Database-backed plugin records staged or installed through the core governance flow.</p>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Discovered Bundled Plugins</p>
            <h2 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $registeredPlugins->count() }}</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Providers currently registered from the local bundled plugin tree.</p>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Installed Plugin Records</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Marketplace intake records will appear here in a staged or quarantined state until approval logic is added.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Plugin</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Security</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($installedPlugins as $installedPlugin)
                            <tr wire:key="installed-plugin-{{ $installedPlugin->id }}">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $installedPlugin->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $installedPlugin->provider_class }}</div>
                                </td>
                                <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ str($installedPlugin->source_type)->replace('_', ' ')->title() }}</td>
                                <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ str($installedPlugin->status)->replace('_', ' ')->title() }}</td>
                                <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ str($installedPlugin->security_status)->replace('_', ' ')->title() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No plugin records have been staged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Registered Bundled Plugins</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">These are the plugin providers already bootstrapped with the application.</p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($registeredPlugins as $plugin)
                    <div class="px-5 py-4" wire:key="registered-plugin-{{ $plugin['provider_class'] }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $plugin['name'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $plugin['provider_class'] }}</p>
                            </div>
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ str($plugin['security_status'])->replace('_', ' ')->title() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No bundled plugins were discovered.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>