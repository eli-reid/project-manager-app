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

@can('viewAny', \App\Domains\Tasks\Models\Task::class)
    <flux:sidebar.item
        icon="check-circle"
        :href="route('tasks.index')"
        :current="request()->routeIs('tasks.*')"
        wire:navigate
        data-test="tasks-sidebar-main-link"
    >
        {{ __('Tasks') }}
    </flux:sidebar.item>
@endcan

@can('reports.financial.view')
    <flux:sidebar.item
        icon="document-text"
        :href="route('reports.financial.index')"
        :current="request()->routeIs('reports.financial.*')"
        wire:navigate
        data-test="reports-sidebar-main-link"
    >
        {{ __('Reports') }}
    </flux:sidebar.item>
@endcan

@can('payroll-stubs.view-own')
    <flux:sidebar.item
        icon="wallet"
        :href="route('payroll.history')"
        :current="request()->routeIs('payroll.history') || request()->routeIs('payroll.history.show')"
        wire:navigate
        data-test="payroll-sidebar-main-link"
    >
        {{ __('My Payroll') }}
    </flux:sidebar.item>
@endcan

@can('reports.payroll.view')
    <flux:sidebar.item
        icon="chart-bar"
        :href="route('reports.payroll.forecasting.index')"
        :current="request()->routeIs('reports.payroll.forecasting.*')"
        wire:navigate
        data-test="payroll-forecasting-sidebar-link"
    >
        {{ __('Payroll Forecasting') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Domains\Documents\Models\Document::class)
    <flux:sidebar.item
        icon="folder"
        :href="route('documents.index')"
        :current="request()->routeIs('documents.*')"
        wire:navigate
    >
        {{ __('Documents') }}
    </flux:sidebar.item>
@endcan

@php
    $showWebmailLink = app(\App\Core\Cpanel\Services\CpanelService::class)->isConfigured()
        && filled(trim((string) (auth()->user()?->company_email ?? auth()->user()?->username ?? '')));
@endphp

@if ($showWebmailLink)
    <flux:sidebar.item
        icon="envelope"
        :href="route('webmail.redirect')"
        target="_blank"
        rel="noopener noreferrer"
        data-test="user-webmail-sidebar-link"
    >
        {{ __('Webmail') }}
    </flux:sidebar.item>
@endif
