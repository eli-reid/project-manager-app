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

<flux:sidebar.item href="#" icon="clipboard-pen-line" :current="false">{{ __('Stock') }}</flux:sidebar.item>
<flux:sidebar.item href="#" icon="folder" :current="false">{{ __('Documents') }}</flux:sidebar.item>
