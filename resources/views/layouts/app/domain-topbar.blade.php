@php
    $routeName = request()->route()?->getName() ?? '';

    $isAccessDomain = str_starts_with($routeName, 'admin.users.')
        || str_starts_with($routeName, 'admin.roles.')
        || str_starts_with($routeName, 'admin.settings.');

    $isTasksDomain = str_starts_with($routeName, 'admin.tasks.')
        || str_starts_with($routeName, 'admin.task-categories.')
        || str_starts_with($routeName, 'admin.task-templates.');

    $isProjectsDomain = str_starts_with($routeName, 'admin.projects.');
    $isClientsDomain = str_starts_with($routeName, 'admin.clients.');
    $isSchedulerDomain = str_starts_with($routeName, 'admin.scheduler.tasks.');
    $isAnnouncementsDomain = str_starts_with($routeName, 'admin.announcements.');
@endphp

@if ($isAccessDomain || $isTasksDomain || $isProjectsDomain || $isClientsDomain || $isSchedulerDomain || $isAnnouncementsDomain)
    <div class="mb-6 rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center gap-2">
            @if ($isAccessDomain)
                @can('viewAny', \App\Core\User\Models\User::class)
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.users.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Users') }}
                    </a>
                @endcan

                @can('viewAny', \App\Core\User\Models\Role::class)
                    <a
                        href="{{ route('admin.roles.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.roles.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Roles') }}
                    </a>
                @endcan

                @can('viewAny', \App\Core\Settings\Models\SettingsSqlite::class)
                    @if (Route::has('admin.settings.index'))
                        <a
                            href="{{ route('admin.settings.index') }}"
                            class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.settings.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                            wire:navigate
                        >
                            {{ __('Settings') }}
                        </a>
                    @endif
                @endcan
            @endif

            @if ($isTasksDomain)
                @can('viewAny', \App\Domains\Tasks\Models\Task::class)
                    <a
                        href="{{ route('admin.tasks.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.tasks.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Tasks') }}
                    </a>
                @endcan

                @can('viewAny', \App\Domains\Tasks\Models\TaskCategory::class)
                    <a
                        href="{{ route('admin.task-categories.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.task-categories.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Categories') }}
                    </a>
                @endcan

                @can('viewAny', \App\Domains\Tasks\Models\TaskTemplate::class)
                    <a
                        href="{{ route('admin.task-templates.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.task-templates.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Templates') }}
                    </a>
                @endcan
            @endif

            @if ($isProjectsDomain)
                @can('viewAny', \App\Domains\Projects\Models\Project::class)
                    <a
                        href="{{ route('admin.projects.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.projects.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Projects') }}
                    </a>
                @endcan
            @endif

            @if ($isClientsDomain)
                @can('viewAny', \App\Domains\Clients\Models\Client::class)
                    <a
                        href="{{ route('admin.clients.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.clients.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Clients') }}
                    </a>
                @endcan
            @endif

            @if ($isSchedulerDomain)
                @can('viewAny', \App\Core\Scheduler\Models\ScheduledTask::class)
                    <a
                        href="{{ route('admin.scheduler.tasks.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.scheduler.tasks.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Scheduler') }}
                    </a>
                @endcan
            @endif

            @if ($isAnnouncementsDomain)
                @can('viewAny', \App\Core\Announcement\Models\Announcement::class)
                    <a
                        href="{{ route('admin.announcements.index') }}"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm {{ request()->routeIs('admin.announcements.*') ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        wire:navigate
                    >
                        {{ __('Announcements') }}
                    </a>
                @endcan
            @endif
        </div>
    </div>
@endif
