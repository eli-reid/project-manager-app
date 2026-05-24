@php
    $user = auth()->user();
    $canViewProjects = $user?->can('viewAny', \App\Domains\Projects\Models\Project::class) ?? false;
    $canViewTimecards = $user?->can('viewAny', \App\Domains\Timecards\Models\Timecard::class) ?? false;
    $canViewDailies = $user?->can('viewAny', \App\Domains\Dailies\Models\DailyReport::class) ?? false;
    $canViewStock = $user?->can('viewAny', \App\Domains\Stock\Models\StockOrder::class) ?? false;
    $canViewDocuments = $user?->can('viewAny', \App\Domains\Documents\Models\Document::class) ?? false;
@endphp

@if ($canViewProjects)
    <flux:sidebar.item
        icon="briefcase"
        :href="route('projects.index')"
        :current="request()->routeIs('projects.*')"
        wire:navigate
        data-test="projects-sidebar-main-link"
    >
        {{ __('My Projects') }}
    </flux:sidebar.item>
@endif

@if ($canViewTimecards)
    <flux:sidebar.item
        icon="clock"
        :href="route('timecards.index')"
        :current="request()->routeIs('timecards.index') || request()->routeIs('timecards.show')"
        wire:navigate
        data-test="timecards-sidebar-main-link"
    >
        {{ __('My Timecards') }}
    </flux:sidebar.item>
@endif

@if ($canViewDailies)
    <flux:sidebar.item
        icon="clipboard-document-list"
        :href="route('dailies.index')"
        :current="request()->routeIs('dailies.*')"
        wire:navigate
        data-test="dailies-sidebar-main-link"
    >
        {{ __('My Dailies') }}
    </flux:sidebar.item>
@endif

@if ($canViewStock)
    <flux:sidebar.item
        icon="archive-box"
        :href="route('stock-orders.index')"
        :current="request()->routeIs('stock-orders.index') || request()->routeIs('stock-orders.show') || request()->routeIs('stock-orders.create') || request()->routeIs('stock-orders.edit')"
        wire:navigate
    >
        {{ __('My Stock Orders') }}
    </flux:sidebar.item>
@endif

@if ($canViewDocuments)
    <flux:sidebar.item
        icon="folder"
        :href="route('documents.index')"
        :current="request()->routeIs('documents.*')"
        wire:navigate
    >
        {{ __('Documents') }}
    </flux:sidebar.item>
@endif
