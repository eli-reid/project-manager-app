<x-layouts::mobile :title="__('Dashboard')">
    @php
        $sectionLabels = [
            'primary' => 'General',
            'personal' => 'My Work',
            'operations' => 'Operations',
            'alerts' => 'Alerts',
            'admin' => 'Administration',
        ];

        $showLabels = count($sections) > 1;
    @endphp

    <div class="flex flex-col gap-5 px-4 py-5">
        @forelse($sections as $sectionKey => $widgets)
            <section class="flex flex-col gap-3">
                @if ($showLabels)
                    <h2 class="text-xs font-semibold uppercase tracking-[0.24em] text-zinc-500">
                        {{ $sectionLabels[$sectionKey] ?? ucfirst($sectionKey) }}
                    </h2>
                @endif

                <div class="grid gap-4">
                    @foreach($widgets as $widget)
                        <div>
                            @livewire($widget['component'], [], $widget['key'].'-mobile')
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-3xl border border-zinc-800 bg-zinc-900 p-6 text-sm text-zinc-400">
                {{ __('Nothing to show here yet.') }}
            </div>
        @endforelse
    </div>
</x-layouts::mobile>