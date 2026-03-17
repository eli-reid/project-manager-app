<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="mx-auto w-full max-w-7xl">
            <flux:navbar class="flex flex-wrap items-center gap-2">
                @can('viewAny', \App\Core\User\Models\User::class)
                    <flux:navbar.item 
                    :href="route('admin.users.index')"
                    wire:navigate
                    >
                        {{ __('Users') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Core\User\Models\Role::class)
                    <flux:navbar.item
                        :href="route('admin.roles.index')"
                        wire:navigate
                    >
                        {{ __('Roles') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Core\Settings\Models\SettingsSqlite::class)
                    @if (Route::has('admin.settings.index'))
                        <flux:navbar.item
                            :href="route('admin.settings.index')"
                            wire:navigate
                        >
                            {{ __('Settings') }}
                        </flux:navbar.item>
                    @endif
                @endcan

                @can('viewAny', \App\Domains\Projects\Models\Project::class)
                    <flux:navbar.item
                        :href="route('admin.projects.index')"
                        wire:navigate
                    >
                        {{ __('Projects') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Domains\Clients\Models\Client::class)
                    <flux:navbar.item
                        :href="route('admin.clients.index')"
                        wire:navigate
                    >
                        {{ __('Clients') }}
                    </flux:navbar.item>
                @endcan

                @if (auth()->user()?->hasPermission('tasks.view') || auth()->user()?->hasPermission('task-categories.view') || auth()->user()?->hasPermission('task-templates.view'))
                    <flux:navbar.item
                        :href="route('admin.tasks.index')"
                        wire:navigate
                    >
                        {{ __('Tasks') }}
                    </flux:navbar.item>
                @endif

                @can('viewAny', \App\Core\Scheduler\Models\ScheduledTask::class)
                    <flux:navbar.item
                        :href="route('admin.scheduler.tasks.index')"
                        wire:navigate
                    >
                        {{ __('Scheduler') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Core\Announcement\Models\Announcement::class)
                    <flux:navbar.item
                        :href="route('admin.announcements.index')"
                        wire:navigate
                    >
                        {{ __('Announcements') }}
                    </flux:navbar.item>
                @endcan
            </flux:navbar>
        </div>
    </x-slot:domainNavbar>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>