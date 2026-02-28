<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            
            <div class="flex space-x-2">
                @can('update', $role)
                <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('Edit') }}
                </a>
                @endcan
                
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 focus:bg-gray-500 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Back to Roles') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
       
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Role Details Card -->
                <div class="col-span-1 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $role->name }}
                                </h2>
                                
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $role->description ?? 'No description provided.' }}
                                </p>
                            </div>
                            
                            <div class="flex flex-col items-end">
                                @if($role->built_in)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ __('Built-in') }}
                                    </span>
                                @endif
                                
                                <span class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $role->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' }}">
                                    {{ $role->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        {{ __('Created') }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $role->created_at->format('M d, Y') }}
                                    </dd>
                                </div>
                                
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        {{ __('Last Updated') }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $role->updated_at->format('M d, Y') }}
                                    </dd>
                                </div>
                                
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        {{ __('Users with this role') }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $role->users_count ?? $role->users()->count() }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div class="mt-6 flex justify-between">
                            @can('update', $role)
                            <form method="POST" action="{{ route('admin.roles.toggle-status', $role) }}" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                                    @if($role->is_active)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ __('Deactivate') }}
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ __('Activate') }}
                                    @endif
                                </button>
                            </form>
                            @endcan

                            @if(!$role->built_in)
                                @can('delete', $role)
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline-block"
                                      onsubmit="return confirm('Are you sure you want to delete this role? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Role Permissions Card -->
                <div class="col-span-1 lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Permissions') }}
                            </h2>
                            
                            @can('update', $role)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                                {{ __('Manage Permissions') }}
                            </a>
                            @endcan
                        </div>

                        @if($role->permissions->isEmpty())
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-4 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('No permissions have been assigned to this role.') }}
                                </p>
                            </div>
                        @else
                            <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
                                @php
                                    // Group permissions by resource
                                    $permissionsByResource = [];
                                    foreach($role->permissions as $permission) {
                                        $resource = $permission->resource;
                                        if (!isset($permissionsByResource[$resource])) {
                                            $permissionsByResource[$resource] = [];
                                        }
                                        $permissionsByResource[$resource][] = $permission;
                                    }
                                    ksort($permissionsByResource);
                                @endphp

                                <div x-data="{ activeTab: Object.keys({{ json_encode($permissionsByResource) }})[0] || '' }">
                                    <!-- Tabs -->
                                    <div class="bg-gray-50 dark:bg-gray-700 px-1 py-2 overflow-x-auto">
                                        <div class="flex space-x-1">
                                            @foreach($permissionsByResource as $resource => $permissions)
                                                <button @click="activeTab = '{{ $resource }}'" 
                                                        :class="{ 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400': activeTab === '{{ $resource }}', 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== '{{ $resource }}' }" 
                                                        class="px-3 py-2 text-sm font-medium rounded-md">
                                                    {{ ucfirst($resource) }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Tab Panels -->
                                    <div class="p-4">
                                        @foreach($permissionsByResource as $resource => $permissions)
                                            <div x-show="activeTab === '{{ $resource }}'" class="space-y-2">
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                    @foreach($permissions as $permission)
                                                        <div class="flex items-center p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 dark:text-green-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                                {{ ucfirst($permission->label ?? $permission->action) }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Role Users Card -->
                <div class="col-span-1 lg:col-span-3 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Users with this Role') }}
                            </h2>
                            
                            @can('update', $role)
                            <a href="{{ route('admin.roles.users', $role) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                                {{ __('Manage Users') }}
                            </a>
                            @endcan
                        </div>

                        @php
                            $users = $role->users()->take(5)->get();
                            $totalUsers = $role->users()->count();
                        @endphp

                        @if($users->isEmpty())
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-4 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('No users have been assigned to this role.') }}
                                </p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('Name') }}
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('Email') }}
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                {{ __('Status') }}
                                            </th>
                                            <th scope="col" class="relative px-6 py-3">
                                                <span class="sr-only">{{ __('Actions') }}</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($users as $user)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $user->name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $user->email }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' }}">
                                                        {{ $user->is_active ? __('Active') : __('Inactive') }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                        {{ __('Edit') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @if($totalUsers > 5)
                                <div class="mt-4 text-center">
                                    <a href="{{ route('admin.roles.users', $role) }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        {{ __('View all') }} {{ $totalUsers }} {{ __('users') }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>