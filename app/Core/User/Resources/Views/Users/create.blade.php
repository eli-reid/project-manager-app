<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add New User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf

                        {{-- Add this informational alert where appropriate in your form --}}
                        @if(config('services.cpanel.auto_create_emails', false))
                            <div class="mb-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-600" role="alert">
                                <p>{{ __('An email account will be automatically created using this user\'s login password.') }}</p>
                                <p class="mt-1">{{ __('When users change their password, their email password will sync automatically.') }}</p>
                            </div>
                        @endif

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Username will be automatically generated from the name</p>
                        </div>

                        <!-- Username (optional override) -->
                        <div class="mt-4">
                            <x-input-label for="username" :value="__('Username (optional)')" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" placeholder="Leave blank to auto-generate" />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Used for login and company email. Auto-generated if left blank.</p>
                        </div>

                        <!-- Email -->
                        <div class="mt-4">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Roles -->
                        <div class="mt-4">
                            <x-input-label for="roles" :value="__('Roles')" />
                            <select id="roles" name="roles[]" multiple class="mt-1 block w-full">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                        </div>

                        <!-- Timecard Required -->
                        <div class="mt-4">
                            <div class="flex items-center">
                                <input id="requires_timecard_submission" 
                                       name="requires_timecard_submission" 
                                       type="checkbox" 
                                       value="1"
                                       {{ old('requires_timecard_submission') ? 'checked' : '' }}
                                       class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                                <label for="requires_timecard_submission" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Requires Timecard Submission') }}
                                </label>
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Check if this user is required to submit timecards</p>
                            <x-input-error :messages="$errors->get('requires_timecard_submission')" class="mt-2" />
                        </div>

                        <!-- Pay Rates Section -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pay Rates</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Standard Rate -->
                                <div>
                                    <x-input-label for="standard_rate" :value="__('Standard Rate')" />
                                    <x-text-input id="standard_rate" 
                                                 name="standard_rate" 
                                                 type="number" 
                                                 step="0.01"
                                                 class="mt-1 block w-full" 
                                                 :value="old('standard_rate')" />
                                    <x-input-error :messages="$errors->get('standard_rate')" class="mt-2" />
                                </div>

                                <!-- Overtime Rate -->
                                <div>
                                    <x-input-label for="overtime_rate" :value="__('Overtime Rate')" />
                                    <x-text-input id="overtime_rate" 
                                                 name="overtime_rate" 
                                                 type="number" 
                                                 step="0.01"
                                                 class="mt-1 block w-full" 
                                                 :value="old('overtime_rate')" />
                                    <x-input-error :messages="$errors->get('overtime_rate')" class="mt-2" />
                                </div>

                                <!-- Double Time Rate -->
                                <div>
                                    <x-input-label for="double_time_rate" :value="__('Double Time Rate')" />
                                    <x-text-input id="double_time_rate" 
                                                 name="double_time_rate" 
                                                 type="number" 
                                                 step="0.01"
                                                 class="mt-1 block w-full" 
                                                 :value="old('double_time_rate')" />
                                    <x-input-error :messages="$errors->get('double_time_rate')" class="mt-2" />
                                </div>

                                <!-- Holiday Rate -->
                                <div>
                                    <x-input-label for="holiday_rate" :value="__('Holiday Rate')" />
                                    <x-text-input id="holiday_rate" 
                                                 name="holiday_rate" 
                                                 type="number" 
                                                 step="0.01"
                                                 class="mt-1 block w-full" 
                                                 :value="old('holiday_rate')" />
                                    <x-input-error :messages="$errors->get('holiday_rate')" class="mt-2" />
                                </div>

                                <!-- Effective Date -->
                                <div>
                                    <x-input-label for="effective_date" :value="__('Effective Date')" />
                                    <x-text-input id="effective_date" 
                                                 name="effective_date" 
                                                 type="date" 
                                                 class="mt-1 block w-full" 
                                                 :value="old('effective_date')" />
                                    <x-input-error :messages="$errors->get('effective_date')" class="mt-2" />
                                </div>

                                <!-- End Date -->
                                <div>
                                    <x-input-label for="end_date" :value="__('End Date (Optional)')" />
                                    <x-text-input id="end_date" 
                                                 name="end_date" 
                                                 type="date" 
                                                 class="mt-1 block w-full" 
                                                 :value="old('end_date')" />
                                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mt-6">
                                <x-input-label for="notes" :value="__('Notes')" />
                                <textarea id="notes" 
                                          name="notes" 
                                          rows="3" 
                                          class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('Add User') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>