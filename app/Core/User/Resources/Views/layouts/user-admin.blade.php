<x-layouts::app.sidebar :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="mx-auto w-full max-w-7xl">
            <flux:navbar class="flex flex-wrap items-center gap-2">
                @can('viewAny', \App\Core\User\Models\User::class)
                    <flux:navbar.item
                        :href="route('admin.users.index')"
                        :current="request()->routeIs('admin.users.*')"
                        wire:navigate
                    >
                        {{ __('Users') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Core\Auth\Role\Models\Role::class)
                    <flux:navbar.item
                        :href="route('admin.roles.index')"
                        :current="request()->routeIs('admin.roles.*')"
                        wire:navigate
                    >
                        {{ __('Roles') }}
                    </flux:navbar.item>
                @endcan
            </flux:navbar>
        </div>
    </x-slot:domainNavbar>

    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>