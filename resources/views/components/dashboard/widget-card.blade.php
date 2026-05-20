@props([
    'heading' => null,
    'subheading' => null,
])

<section {{ $attributes->class('rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900') }}>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
        <div class="min-w-0">
            @isset($title)
                {{ $title }}
            @elseif ($heading !== null)
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $heading }}</h3>
            @endisset

            @isset($description)
                {{ $description }}
            @elseif ($subheading !== null && $subheading !== '')
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
            @endif
        </div>

        @isset($action)
            <div class="max-w-full self-start sm:shrink-0">
                {{ $action }}
            </div>
        @endisset
    </div>

    {{ $slot }}
</section>