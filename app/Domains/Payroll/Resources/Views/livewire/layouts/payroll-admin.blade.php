<x-layouts::app :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="w-full">
            <flux:navbar class="flex flex-wrap items-center gap-2">

                @can('payroll-timecards.view')
                    <flux:navbar.item
                        :href="route('admin.timecards.index')"
                        :current="request()->routeIs('admin.timecards.*')"
                        wire:navigate
                    >
                        {{ __('Timecards') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAll', \App\Domains\Dailies\Models\DailyReport::class)
                    <flux:navbar.item
                        :href="route('admin.dailies.index')"
                        :current="request()->routeIs('admin.dailies.*')"
                        wire:navigate
                    >
                        {{ __('Dailies') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAll', \App\Domains\Timecards\Models\Timecard::class)
                    <flux:navbar.item
                        :href="route('admin.payroll.timecards.review')"
                        :current="request()->routeIs('admin.payroll.timecards.*')"
                        wire:navigate
                    >
                        {{ __('Validate Timecard') }}
                    </flux:navbar.item>
                @endcan

                @can('payroll-runs.preview')
                    <flux:navbar.item
                        :href="route('admin.payroll.reports.weekly-employee-hours')"
                        :current="request()->routeIs('admin.payroll.reports.weekly-employee-hours')"
                        wire:navigate
                    >
                        {{ __('Weekly Employee Hours') }}
                    </flux:navbar.item>

                    <flux:navbar.item
                        :href="route('admin.payroll.reports.weekly-hour-adjustments')"
                        :current="request()->routeIs('admin.payroll.reports.weekly-hour-adjustments')"
                        wire:navigate
                    >
                        {{ __('Weekly Hour Adjustments') }}
                    </flux:navbar.item>
                @endcan

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

    {{ $slot }}
</x-layouts::app>