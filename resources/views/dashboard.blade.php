<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        @forelse($sections as $sectionKey => $widgets)
            <div class="grid gap-4 lg:grid-cols-3">
                @foreach($widgets as $widget)
                    <div class="{{ match($widget['span']) { 'full' => 'lg:col-span-3', 'half' => 'lg:col-span-2', default => 'lg:col-span-1' } }}">
                        @livewire($widget['component'], [], $widget['key'])
                    </div>
                @endforeach
            </div>
        @empty
            <div class="flex items-center justify-center rounded-xl border border-zinc-200 p-12 dark:border-zinc-700">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Nothing to show here yet.</p>
            </div>
        @endforelse
    </div>
</x-layouts::app>
