<section class="w-full space-y-6">
    <div class="space-y-2">
        <flux:heading size="xl">{{ __('Operational Reports') }}</flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Operations-focused reporting workspace registered by owning domains.') }}
        </flux:text>
    </div>

    @if (count($reportCards) === 0)
        <article class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('No operational report definitions are currently registered.') }}
            </flux:text>
        </article>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($reportCards as $reportCard)
                <a href="{{ route($reportCard['route']) }}" wire:navigate class="group rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-500">
                    <div class="space-y-2">
                        <flux:heading size="lg" class="group-hover:text-sky-700 dark:group-hover:text-sky-300">{{ __($reportCard['title']) }}</flux:heading>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ __($reportCard['description']) }}</flux:text>
                    </div>
                    <div class="mt-4">
                        <flux:badge :color="$reportCard['badge_color']">{{ __($reportCard['badge_label']) }}</flux:badge>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
