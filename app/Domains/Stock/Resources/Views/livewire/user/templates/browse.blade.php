<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Order Templates</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Use a saved template to quickly fill out a new stock order.</p>
        </div>
        <a href="{{ route('stock-orders.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">My Orders</a>
    </div>

    <div>
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search templates..."
            class="w-full max-w-sm rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
        />
    </div>

    @if ($templates->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No templates available{{ $search ? ' matching your search' : '' }}.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($templates as $template)
                <div wire:key="template-{{ $template->id }}" class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $template->name }}</h3>
                            @if ($template->description)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $template->description }}</p>
                            @endif
                        </div>
                        @php
                            $urgencyColors = [
                                'low' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                'high' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            ];
                        @endphp
                        <span class="inline-flex shrink-0 rounded-md px-2 py-1 text-xs font-semibold {{ $urgencyColors[$template->urgency] ?? 'bg-zinc-100 text-zinc-600' }}">
                            {{ ucfirst($template->urgency) }}
                        </span>
                    </div>

                    @if (!empty($template->template_items))
                        <ul class="mt-3 space-y-1 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            @foreach (array_slice($template->template_items, 0, 4) as $item)
                                <li class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                                    <span>{{ $item['item_name'] ?? 'Item' }}</span>
                                    <span class="font-medium text-zinc-500">×{{ $item['quantity'] ?? 1 }}</span>
                                </li>
                            @endforeach
                            @if (count($template->template_items) > 4)
                                <li class="text-xs text-zinc-400 dark:text-zinc-500">+{{ count($template->template_items) - 4 }} more...</li>
                            @endif
                        </ul>
                    @endif

                    <div class="mt-4 flex items-center gap-2 pt-2">
                        @can('create', \App\Domains\Stock\Models\StockOrder::class)
                            <a
                                href="{{ route('stock-orders.templates.from', $template) }}"
                                wire:navigate
                                class="flex-1 rounded-md bg-zinc-900 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                            >
                                Use Template
                            </a>
                        @endcan
                        @if ($template->is_global)
                            <span class="rounded-md border border-zinc-200 px-2 py-2 text-xs text-zinc-400 dark:border-zinc-700 dark:text-zinc-500">Global</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
