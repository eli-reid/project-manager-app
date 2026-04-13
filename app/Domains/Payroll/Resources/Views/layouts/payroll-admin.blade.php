<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="mx-auto w-full max-w-7xl">
            <flux:navbar class="flex flex-wrap items-center gap-2">
                @can('payroll-rates.view')
                    <flux:navbar.item
                        :href="route('admin.payroll.rates.index')"
                        :current="request()->routeIs('admin.payroll.rates.*')"
                        wire:navigate
                    >
                        {{ __('Rates') }}
                    </flux:navbar.item>

                    <flux:navbar.item
                        :href="route('admin.payroll.rate-types.index')"
                        :current="request()->routeIs('admin.payroll.rate-types.*')"
                        wire:navigate
                    >
                        {{ __('Rate Types') }}
                    </flux:navbar.item>
                @endcan

                @can('payroll-timecards.view')
                    <flux:navbar.item
                        :href="route('admin.payroll.timecards.review')"
                        :current="request()->routeIs('admin.payroll.timecards.*')"
                        wire:navigate
                    >
                        {{ __('Timecards') }}
                    </flux:navbar.item>
                @endcan

                @can('payroll-runs.preview')
                    <flux:navbar.item
                        :href="route('admin.payroll.runs.index')"
                        :current="request()->routeIs('admin.payroll.runs.*')"
                        wire:navigate
                    >
                        {{ __('Runs') }}
                    </flux:navbar.item>
                @endcan
            </flux:navbar>
        </div>
    </x-slot:domainNavbar>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>