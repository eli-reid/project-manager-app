<x-layouts::app :title="__('Dashboard')">
    @php
        $sectionLabels = [
            'primary'    => 'General',
            'personal'   => 'My Work',
            'operations' => 'Operations',
            'alerts'     => 'Alerts',
            'admin'      => 'Administration',
        ];
        $showLabels = count($sections) > 1;
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @forelse($sections as $sectionKey => $widgets)
            <div class="flex flex-col gap-3">
                @if ($showLabels)
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ $sectionLabels[$sectionKey] ?? ucfirst($sectionKey) }}
                    </h2>
                @endif
                <div class="grid gap-4 lg:grid-cols-6">
                    @php
                        $isSingleWidgetSection = count($widgets) === 1;
                    @endphp
                    @foreach($widgets as $widget)
                        <div class="{{ $isSingleWidgetSection
                            ? 'lg:col-span-6'
                            : match($widget['span']) {
                                'full' => 'lg:col-span-6',
                                'half' => 'lg:col-span-3',
                                default => 'lg:col-span-2',
                            } }}">
                            @livewire($widget['component'], [], $widget['key'])
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="flex items-center justify-center rounded-xl border border-zinc-200 p-12 dark:border-zinc-700">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Nothing to show here yet.</p>
            </div>
        @endforelse
    </div>
</x-layouts::app>
