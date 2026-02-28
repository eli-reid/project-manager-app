<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p>{{ session('error') }}</p>
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                        {{ __('Assign Users to') }} {{ $role->name }} {{ __('Role') }}
                    </h2>

                    <form action="{{ route('admin.roles.assign-users.store', $role) }}" method="POST" class="space-y-6">
                        @csrf

                        @if($availableUsers->isEmpty())
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-6 text-center">
                            <p class="text-gray-600 dark:text-gray-400">{{ __('No available users to assign to this role.') }}</p>
                        </div>
                        @else
                        <div x-data="{ selectedUsers: [] }">
                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <x-input-label for="users" :value="__('Select Users')" />
                                    <div class="flex space-x-2">
                                        <button type="button" @click="selectedUsers = []; document.querySelectorAll('input[name=\'users[]\']').forEach(el => el.checked = false)"
                                            class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                            {{ __('Clear All') }}
                                        </button>
                                        <button type="button" @click="selectedUsers = [...document.querySelectorAll('input[name=\'users[]\']')].map(el => el.value); document.querySelectorAll('input[name=\'users[]\']').forEach(el => el.checked = true)"
                                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200">
                                            {{ __('Select All') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-1 bg-gray-50 dark:bg-gray-700 rounded-md p-4 h-64 overflow-y-auto">
                                    @foreach($availableUsers->chunk(3) as $chunk)
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                        @foreach($chunk as $user)
                                        <div class="flex items-start">
                                            <div class="flex items-center h-5">
                                                <input id="user_{{ $user->id }}" name="users[]" value="{{ $user->id }}" type="checkbox"
                                                    x-model="selectedUsers"
                                                    class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="user_{{ $user->id }}" class="font-medium text-gray-700 dark:text-gray-300">
                                                    {{ $user->name }}
                                                </label>
                                                <p class="text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>

                                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span x-text="selectedUsers.length"></span> {{ __('users selected') }}
                                </div>

                                @error('users')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <a href="{{ route('admin.roles.users', $role) }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:bg-gray-400 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 mr-2">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                {{ __('Assign Users') }}
                            </x-primary-button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>