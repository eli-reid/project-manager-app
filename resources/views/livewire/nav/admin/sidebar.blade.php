<div class="contents">
<flux:sidebar.spacer />

@if ($canViewAdminNav)
    <flux:sidebar.header class="in-data-flux-sidebar-collapsed-desktop:hidden">
        <flux:separator data-flux-separator="admin-header" text="{{ __('Administration') }}" class="text-lg" />
    </flux:sidebar.header>

    @if ($canManageAnnouncements)
        <flux:sidebar.item icon="megaphone" :href="route('admin.announcements.index')" :current="request()->routeIs('admin.announcements.*')" wire:navigate data-test="admin-announcements-sidebar-main-link">
            {{ __('Announcements') }}
        </flux:sidebar.item>
    @endif

    @if ($canViewAdminProjects)
        <flux:sidebar.item icon="drafting-compass" :href="route('admin.projects.index')" :current="request()->routeIs('admin.projects.*')" wire:navigate>
            {{ __('Projects') }}
        </flux:sidebar.item>
    @endif

    @if ($canViewAdminAccountingCodes)
        <flux:sidebar.item icon="calculator" :href="route('admin.accounting-codes.index')" :current="request()->routeIs('admin.accounting-codes.*')" wire:navigate>
            {{ __('Accounting Codes') }}
        </flux:sidebar.item>
    @endif

    @if ($canManageUsers)
        <flux:sidebar.item icon="shield-check" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*')" wire:navigate data-test="admin-settings-sidebar-main-link">
            {{ __('User Management') }}
        </flux:sidebar.item>
    @endif

    @if ($showClientManagement)
        <flux:sidebar.item
            icon="building-2"
            :href="$canViewAdminClients ? route('admin.clients.index') : route('admin.addresses.index')"
            :current="request()->routeIs('admin.clients.*')"
            wire:navigate
            data-test="admin-client-management-sidebar-main-link"
        >
            {{ __('Client Management') }}
        </flux:sidebar.item>
    @endif

    @if ($canViewAdminAddresses)
        <flux:sidebar.item
            icon="map-pin"
            :href="route('admin.addresses.index')"
            :current="request()->routeIs('admin.addresses.*')"
            wire:navigate
            data-test="admin-addresses-sidebar-main-link"
        >
            {{ __('Addresses') }}
        </flux:sidebar.item>
    @endif

    @if ($showStockAndInvoices)
        <flux:sidebar.item
            icon="archive-box"
            :href="$canViewAdminStockOrders
                ? route('admin.stock-orders.index')
                : ($canViewAdminStockTemplates
                    ? route('admin.stock-order-templates.index')
                    : route('admin.invoices.index'))"
            :current="request()->routeIs('admin.stock-orders.*') || request()->routeIs('admin.stock-order-templates.*') || request()->routeIs('admin.invoices.*')"
            wire:navigate
            data-test="admin-stock-invoices-sidebar-main-link"
        >
            {{ __('Stock & Invoices') }}
        </flux:sidebar.item>
    @endif


    @if ($canViewAdminDocuments)
        <flux:sidebar.item icon="folder" :href="route('admin.documents.index')" :current="request()->routeIs('admin.documents.*')" wire:navigate>
            {{ __('Documents') }}
        </flux:sidebar.item>
    @endif

    @if ($canManagePayroll)
        <flux:sidebar.item
            icon="banknotes"
            :href="$payrollHref"
            :current="request()->routeIs('admin.payroll.*')"
            wire:navigate
            data-test="admin-payroll-sidebar-main-link"
        >
            {{ __('Payroll') }}
        </flux:sidebar.item>
    @endif

    @if ($canViewReports)
        <flux:sidebar.item
            icon="chart-bar"
            :href="route('admin.reports.index')"
            :current="request()->routeIs('admin.reports.*') || request()->routeIs('reports.*')"
            wire:navigate
            data-test="admin-reports-sidebar-main-link"
        >
            {{ __('Reports') }}
        </flux:sidebar.item>
    @endif

    @if ($canManageSettings)
        <flux:sidebar.item icon="cog" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')" wire:navigate data-test="admin-settings-link">
            {{ __('Settings') }}
        </flux:sidebar.item>
    @endif

    @if ($canViewScheduler)
        <flux:sidebar.item icon="clock" :href="route('admin.scheduler.tasks.index')" :current="request()->routeIs('admin.scheduler.tasks.*')" wire:navigate data-test="admin-scheduler-sidebar-main-link">
            {{ __('Scheduler') }}
        </flux:sidebar.item>
    @endif

    @if ($canViewQueue)
        <flux:sidebar.item icon="server-stack" :href="route('admin.queue.index')" :current="request()->routeIs('admin.queue.*')" wire:navigate data-test="admin-queue-sidebar-main-link">
            {{ __('Queue') }}
        </flux:sidebar.item>
    @endif
@endif
</div>

     


