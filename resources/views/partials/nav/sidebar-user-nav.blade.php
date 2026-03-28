@can('viewAny', \App\Domains\Timecards\Models\Timecard::class)
    <flux:sidebar.item
        icon="clock"
        :href="route('timecards.index')"
        :current="request()->routeIs('timecards.index') || request()->routeIs('timecards.show')"
        wire:navigate
        data-test="timecards-sidebar-main-link"
    >
        {{ __('My Timecards') }}
    </flux:sidebar.item>
@endcan

@can('create', \App\Domains\Timecards\Models\Timecard::class)
    <flux:sidebar.item
        icon="plus"
        :href="route('timecards.create')"
        :current="request()->routeIs('timecards.create') || request()->routeIs('timecards.edit')"
        wire:navigate
        data-test="timecards-create-sidebar-main-link"
    >
        {{ __('New Timecard') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Domains\Dailies\Models\DailyReport::class)
    <flux:sidebar.item
        icon="clipboard-document-list"
        :href="route('dailies.index')"
        :current="request()->routeIs('dailies.*')"
        wire:navigate
        data-test="dailies-sidebar-main-link"
    >
        {{ __('My Dailies') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Domains\Stock\Models\StockOrder::class)
    <flux:sidebar.item
        icon="archive-box"
        :href="route('stock-orders.index')"
        :current="request()->routeIs('stock-orders.index') || request()->routeIs('stock-orders.show') || request()->routeIs('stock-orders.create') || request()->routeIs('stock-orders.edit')"
        wire:navigate
    >
        {{ __('My Stock Orders') }}
    </flux:sidebar.item>
@endcan

@can('create', \App\Domains\Stock\Models\StockOrder::class)
    <flux:sidebar.item
        icon="archive-box-arrow-down"
        :href="route('stock-orders.templates.browse')"
        :current="request()->routeIs('stock-orders.templates.*')"
        wire:navigate
    >
        {{ __('Order Templates') }}
    </flux:sidebar.item>
@endcan
<flux:sidebar.item href="#" icon="folder" :current="false">{{ __('Documents') }}</flux:sidebar.item>
