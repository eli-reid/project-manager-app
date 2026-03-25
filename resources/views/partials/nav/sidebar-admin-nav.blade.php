@can('admin')
    <flux:sidebar.item icon="shield-check" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.settings.*')" wire:navigate data-test="admin-settings-sidebar-main-link">
        {{ __('Access') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Core\Scheduler\Models\ScheduledTask::class)
    <flux:sidebar.item icon="clock" :href="route('admin.scheduler.tasks.index')" :current="request()->routeIs('admin.scheduler.tasks.*')" wire:navigate data-test="admin-scheduler-sidebar-main-link">
        {{ __('Scheduler') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Core\Announcement\Models\Announcement::class)
    <flux:sidebar.item icon="megaphone" :href="route('admin.announcements.index')" :current="request()->routeIs('admin.announcements.*')" wire:navigate data-test="admin-announcements-sidebar-main-link">
        {{ __('Announcements') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Domains\Projects\Models\Project::class)
    <flux:sidebar.item icon="drafting-compass" :href="route('admin.projects.index')" :current="request()->routeIs('admin.projects.*')" wire:navigate>
        {{ __('Projects') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Domains\Clients\Models\Client::class)
    <flux:sidebar.item icon="building-2" :href="route('admin.clients.index')" :current="request()->routeIs('admin.clients.*')" wire:navigate>
        {{ __('Clients') }}
    </flux:sidebar.item>
@endcan

@if (auth()->user()?->hasPermission('tasks.view') || auth()->user()?->hasPermission('task-categories.view') || auth()->user()?->hasPermission('task-templates.view'))
    <flux:sidebar.item icon="check-circle" :href="route('admin.tasks.index')" :current="request()->routeIs('admin.tasks.*') || request()->routeIs('admin.task-categories.*') || request()->routeIs('admin.task-templates.*')" wire:navigate>
        {{ __('Tasks') }}
    </flux:sidebar.item>
@endif

@can('viewAll', \App\Domains\Timecards\Models\Timecard::class)
    <flux:sidebar.item icon="clock" :href="route('admin.timecards.index')" :current="request()->routeIs('admin.timecards.*')" wire:navigate data-test="admin-timecards-sidebar-main-link">
        {{ __('Timecards') }}
    </flux:sidebar.item>
@endcan

@can('viewAny', \App\Domains\Invoices\Models\Invoice::class)
    <flux:sidebar.item icon="document-text" :href="route('admin.invoices.index')" :current="request()->routeIs('admin.invoices.*')" wire:navigate data-test="admin-invoices-sidebar-main-link">
        {{ __('Invoices') }}
    </flux:sidebar.item>
@endcan
