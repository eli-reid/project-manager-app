<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit User') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Main User Edit Form -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Name, Username and Email Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" 
                                             name="name" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('name', $user->name)" 
                                             required 
                                             autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Username -->
                            <div>
                                <x-input-label for="username" :value="__('Username')" />
                                <x-text-input id="username" 
                                             name="username" 
                                             type="text" 
                                             class="mt-1 block w-full" 
                                             :value="old('username', $user->username)" />
                                <x-input-error :messages="$errors->get('username')" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Used for login and company email (e.g., {{ $user->username ?? 'johnd' }})</p>
                            </div>
                        </div>

                        <!-- Email Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <!-- Email -->
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email"
                                             name="email"
                                             type="email"
                                             class="mt-1 block w-full"
                                             :value="old('email', $user->email)"
                                             required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Password Row (optional for edit) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div>
                                <x-input-label for="password" :value="__('Password (leave blank to keep current)')" />
                                <x-text-input id="password" 
                                             name="password" 
                                             type="password" 
                                             class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="password_confirmation" 
                                             name="password_confirmation" 
                                             type="password" 
                                             class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Add this after the password fields -->
                        <div class="mt-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    @if(!$user->isSystem())
                                        <input type="hidden" name="is_active" value="0">
                                    @endif
                                    <input id="is_active" name="is_active" type="checkbox" value="1" 
                                        {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                        {{ $user->isSystem() ? 'disabled' : '' }}
                                        class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_active" class="font-medium text-gray-700 dark:text-gray-300">Active Account</label>
                                    <p class="text-gray-500 dark:text-gray-400">
                                        {{ $user->isSystem() ? 'System users cannot be disabled.' : 'Enable or disable this user\'s ability to login and use the system.' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Timecard Requirement -->
                        <div class="mt-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="hidden" name="requires_timecard_submission" value="0">
                                    <input id="requires_timecard_submission" name="requires_timecard_submission" type="checkbox" value="1" 
                                        {{ old('requires_timecard_submission', $user->requires_timecard_submission) ? 'checked' : '' }}
                                        class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="requires_timecard_submission" class="font-medium text-gray-700 dark:text-gray-300">Requires Timecard Submission</label>
                                    <p class="text-gray-500 dark:text-gray-400">
                                        User must submit timecards and will receive automated reminders when timecards are due.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Dual Listbox Role Selection -->
                        <div class="mt-6">
                            <x-input-label :value="__('User Roles')" class="mb-3" />
                            
                            <!-- Improved table layout with fixed sizing -->
                            <table class="w-full border-separate border-spacing-0">
                                <tr>
                                    <!-- Fixed width and height for the first listbox -->
                                    <td class="align-top" style="min-height: 250px; max-width: 50px; min-width: 50px;">
                                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Available Roles</h3>
                                        <div class="h-52 overflow-hidden border border-gray-300 dark:border-gray-700 rounded-md shadow-sm">
                                            <select id="available-roles" multiple
                                                   class="w-full h-full border-0 focus:ring-0 focus:border-transparent dark:bg-gray-900 dark:text-gray-300">
                                                @foreach($roles as $role)
                                                    @if(!$user->roles->contains($role))
                                                        <option value="{{ $role->id }}"
                                                                {{ in_array($role->id, old('roles', [])) ? 'disabled' : '' }}>
                                                            {{ $role->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    
                                    <!-- Fixed width for the buttons column -->
                                    <td class="w-[10%] align-middle text-center">
                                        <div class="flex flex-col space-y-4 justify-center items-center">
                                            <button type="button" id="add-role" 
                                                    class="px-3 py-2 w-12 bg-blue-500 text-white rounded-md hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                            <button type="button" id="remove-role" 
                                                    class="px-3 py-2 w-12 bg-gray-500 text-white rounded-md hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    
                                    <!-- Fixed width and height for the second listbox -->
                                    <td class="w-[45%] align-top" style="min-height: 250px; max-width: 50px; min-width: 50px;">
                                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Selected Roles</h3>
                                        <div class="h-52 overflow-hidden border border-gray-300 dark:border-gray-700 rounded-md shadow-sm">
                                            <select id="selected-roles" multiple 
                                                   class="w-full h-full border-0 focus:ring-0 focus:border-transparent dark:bg-gray-900 dark:text-gray-300">
                                                @php
                                                    $userRoleIds = old('roles', $user->roles->pluck('id')->toArray());
                                                @endphp
                                                @foreach($roles as $role)
                                                    @if(in_array($role->id, $userRoleIds))
                                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="hidden-roles-container">
                                            @foreach($userRoleIds as $roleId)
                                                <input type="hidden" name="roles[]" value="{{ $roleId }}">
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                                <span class="inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    If no roles are selected, the default user role will be assigned.
                                </span>
                            </p>
                            <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ml-4">
                                {{ __('Update User') }}
                            </x-primary-button>
                        </div>
                    </form>

                    <!-- Reset Password Form -->
                    <div class="mt-6">
                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                            @csrf
                            <x-primary-button class="ml-4 bg-red-500 hover:bg-red-600">
                                {{ __('Reset Password') }}
                            </x-primary-button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pay Rates Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Pay Rates</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage the user's pay rates.</p>

                    <!-- Add Pay Rate Form -->
                    <div x-data="payRateForm({{ json_encode(['userId' => $user->id]) }})">
                        <!-- Form for adding/editing pay rates -->
                        <form @submit.prevent="savePayRate" class="mt-6">
                            <input type="hidden" x-model="formData.id">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <!-- Pay Rate Type -->
                                <div>
                                    <label for="pay_rate_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                    <select x-model="formData.pay_rate_type_id" id="pay_rate_type_id" 
                                            x-bind:disabled="editMode"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Select a type</option>
                                        @foreach($payRateTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    <p x-show="errors.pay_rate_type_id" x-text="errors.pay_rate_type_id" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                                </div>

                                <!-- Rate -->
                                <div>
                                    <label for="rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rate</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                                        </div>
                                        <input type="number" x-model="formData.rate" id="rate" step="0.01" min="0" 
                                               class="pl-7 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                                    </div>
                                    <p x-show="errors.rate" x-text="errors.rate" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                                </div>

                                <!-- Overtime Multiplier -->
                                <div>
                                    <label for="overtime_multiplier" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Overtime Multiplier</label>
                                    <input type="number" x-model="formData.overtime_multiplier" id="overtime_multiplier" step="0.1" min="1" 
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <p x-show="errors.overtime_multiplier" x-text="errors.overtime_multiplier" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                                </div>

                                <!-- Double Time Multiplier -->
                                <div>
                                    <label for="double_time_multiplier" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Double Time Multiplier</label>
                                    <input type="number" x-model="formData.double_time_multiplier" id="double_time_multiplier" step="0.1" min="1" 
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <p x-show="errors.double_time_multiplier" x-text="errors.double_time_multiplier" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                                </div>

                                <!-- Effective Date -->
                                <div>
                                    <label for="effective_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Effective Date</label>
                                    <input type="date" x-model="formData.effective_date" id="effective_date" 
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                                    <p x-show="errors.effective_date" x-text="errors.effective_date" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                                </div>

                                <!-- End Date -->
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date (Optional)</label>
                                    <input type="date" x-model="formData.end_date" id="end_date" 
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <p x-show="errors.end_date" x-text="errors.end_date" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                                </div>

                                <!-- Notes -->
                                <div class="sm:col-span-2">
                                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                                    <textarea x-model="formData.notes" id="notes" rows="3" 
                                              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                    <p x-show="errors.notes" x-text="errors.notes" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-between items-center">
                                <div>
                                    <button x-show="editMode" @click="cancelEdit" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Cancel
                                    </button>
                                </div>
                                <div>
                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-700 hover:bg-indigo-700 dark:hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <span x-text="editMode ? 'Update Pay Rate' : 'Add Pay Rate'"></span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Pay Rates List -->
                        <div class="mt-8">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Current Pay Rates</h4>
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rate</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Overtime</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Double Time</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Effective Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">End Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Notes</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @forelse($user->payRates as $payRate)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $payRate->payRateType->name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">${{ number_format($payRate->rate, 2) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $payRate->overtime_multiplier }}x</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $payRate->double_time_multiplier }}x</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $payRate->effective_date->format('M d, Y') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">{{ $payRate->end_date ? $payRate->end_date->format('M d, Y') : '-' }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">{{ $payRate->notes }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 space-x-2">
                                                    <button
                                                        @click="editPayRate('{{ $payRate->id }}')"
                                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                                    >Edit</button>
                                                    <form method="POST" action="{{ route('admin.users.pay-rates.destroy', [$user, $payRate]) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">No pay rates found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Status alerts -->
                        <div x-show="showSuccessAlert" class="mt-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded" role="alert">
                            <div class="flex">
                                <div class="py-1">
                                    <svg class="fill-current h-6 w-6 text-green-500 dark:text-green-400 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p x-text="statusMessage"></p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button @click="showSuccessAlert = false" class="text-green-700 dark:text-green-300 hover:text-green-800 dark:hover:text-green-200">
                                        <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10 8.586L2.929 1.515 1.515 2.929 8.586 10l-7.071 7.071 1.414 1.414L10 11.414l7.071-7.071-1.414-1.414L10 8.586z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Email Section -->
            <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Company Email') }}
                    </h3>
                    
                    <div class="mt-4">
                        @if($user->company_email)
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Current company email') }}</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $user->company_email }}</p>
                                </div>
                                
                                <form method="POST" action="{{ route('admin.users.generate-company-email', $user) }}" class="inline">
                                    @csrf
                                    <x-button type="submit" class="bg-yellow-600 hover:bg-yellow-700">
                                        {{ __('Regenerate Email') }}
                                    </x-button>
                                </form>
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('No company email has been generated for this user.') }}
                                </p>
                                
                                <form method="POST" action="{{ route('admin.users.generate-company-email', $user) }}" class="inline">
                                    @csrf
                                    <x-button type="submit" class="bg-indigo-600 hover:bg-indigo-700">
                                        {{ __('Generate Company Email') }}
                                    </x-button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // PayRate Form handling with Alpine.js
        function payRateForm(config) {
            return {
                userId: config.userId,
                editMode: false,
                formData: {
                    id: null,
                    pay_rate_type_id: '',
                    rate: '',
                    overtime_multiplier: 1.5,
                    double_time_multiplier: 2.0,
                    effective_date: new Date().toISOString().split('T')[0],
                    end_date: '',
                    notes: ''
                },
                errors: {},
                showSuccessAlert: false,
                statusMessage: '',
                
                editPayRate(payRateId) {
                    this.editMode = true;
                    this.errors = {};
                    
                    // Fetch the pay rate data
                    fetch(`/admin/users/${this.userId}/pay-rates/${payRateId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.formData = data.payRate;
                                
                                // Scroll to the form
                                setTimeout(() => {
                                    document.getElementById('pay_rate_type_id').scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }, 100);
                            } else {
                                this.showErrorMessage(data.error || 'Failed to load pay rate data');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching pay rate:', error);
                            this.showErrorMessage('An error occurred while fetching the pay rate');
                        });
                },
                
                savePayRate() {
                    this.errors = {};
                    
                    // Validate form
                    if (!this.formData.pay_rate_type_id) {
                        this.errors.pay_rate_type_id = 'Please select a pay rate type';
                    }
                    
                    if (!this.formData.rate || isNaN(parseFloat(this.formData.rate)) || parseFloat(this.formData.rate) < 0) {
                        this.errors.rate = 'Please enter a valid rate';
                    }
                    
                    if (!this.formData.effective_date) {
                        this.errors.effective_date = 'Effective date is required';
                    }
                    
                    if (this.formData.end_date && this.formData.effective_date && this.formData.end_date <= this.formData.effective_date) {
                        this.errors.end_date = 'End date must be after the effective date';
                    }
                    
                    // If there are validation errors, stop submission
                    if (Object.keys(this.errors).length > 0) {
                        return;
                    }
                    
                    // Prepare form data
                    const formData = new FormData();
                    Object.keys(this.formData).forEach(key => {
                        if (this.formData[key] !== null && this.formData[key] !== undefined) {
                            formData.append(key, this.formData[key]);
                        }
                    });
                    
                    // Prepare request
                    const method = this.editMode ? 'PUT' : 'POST';
                    const url = this.editMode 
                        ? `/admin/users/${this.userId}/pay-rates/${this.formData.id}`
                        : `/admin/users/${this.userId}/pay-rates`;
                    
                    // Add CSRF token
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    if (this.editMode) {
                        formData.append('_method', 'PUT');
                    }
                    
                    // Submit form
                    fetch(url, {
                        method: 'POST', // Always POST for FormData
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showSuccessMessage(this.editMode ? 'Pay rate updated successfully' : 'Pay rate added successfully');
                            // Reload the page to show the updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            this.showErrorMessage(data.message || 'An error occurred');
                            if (data.errors) {
                                this.errors = data.errors;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error saving pay rate:', error);
                        this.showErrorMessage('An error occurred while saving the pay rate');
                    });
                },
                
                cancelEdit() {
                    this.editMode = false;
                    this.resetForm();
                    this.errors = {};
                },
                
                resetForm() {
                    this.formData = {
                        id: null,
                        pay_rate_type_id: '',
                        rate: '',
                        overtime_multiplier: 1.5,
                        double_time_multiplier: 2.0,
                        effective_date: new Date().toISOString().split('T')[0],
                        end_date: '',
                        notes: ''
                    };
                },
                
                showSuccessMessage(message) {
                    this.statusMessage = message;
                    this.showSuccessAlert = true;
                    setTimeout(() => {
                        this.showSuccessAlert = false;
                    }, 5000);
                },
                
                showErrorMessage(message) {
                    alert(message);
                }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            const availableRoles = document.getElementById('available-roles');
            const selectedRoles = document.getElementById('selected-roles');
            const addRoleBtn = document.getElementById('add-role');
            const removeRoleBtn = document.getElementById('remove-role');
            const hiddenContainer = document.getElementById('hidden-roles-container');
            const form = document.querySelector('form');

            // Select all available options if none are selected when clicking add
            addRoleBtn.addEventListener('click', function() {
                if (availableRoles.selectedOptions.length === 0 && availableRoles.options.length > 0) {
                    // Find the first non-disabled option
                    const firstEnabledOption = Array.from(availableRoles.options).find(option => !option.disabled);
                    if (firstEnabledOption) {
                        firstEnabledOption.selected = true;
                    }
                }
                moveOptions(availableRoles, selectedRoles);
                updateHiddenInputs();
            });

            // Select all selected options if none are selected when clicking remove
            removeRoleBtn.addEventListener('click', function() {
                if (selectedRoles.selectedOptions.length === 0 && selectedRoles.options.length > 0) {
                    // Select first option by default
                    selectedRoles.options[0].selected = true;
                }
                moveOptions(selectedRoles, availableRoles);
                updateHiddenInputs();
            });

            // Double click to move between lists
            availableRoles.addEventListener('dblclick', function() {
                moveOptions(availableRoles, selectedRoles);
                updateHiddenInputs();
            });

            selectedRoles.addEventListener('dblclick', function() {
                moveOptions(selectedRoles, availableRoles);
                updateHiddenInputs();
            });

            // Update hidden inputs before form submission
            form.addEventListener('submit', function() {
                updateHiddenInputs();
                
                // Make sure we submit at least one role if there are any
                if (selectedRoles.options.length === 0 && availableRoles.options.length > 0) {
                    alert('Please select at least one role for this user.');
                    return false;
                }
            });

            // Function to move selected options between lists
            function moveOptions(fromSelect, toSelect) {
                const selectedOptions = Array.from(fromSelect.selectedOptions);
                
                if (selectedOptions.length === 0) return;
                
                selectedOptions.forEach(option => {
                    const newOption = document.createElement('option');
                    newOption.value = option.value;
                    newOption.text = option.text;
                    toSelect.add(newOption);
                    
                    // If moving to available, remove disabled attribute
                    if (toSelect === availableRoles) {
                        newOption.disabled = false;
                    }
                    // If moving to selected, disable in available
                    else if (toSelect === selectedRoles) {
                        option.disabled = true;
                    }
                });
                
                // Remove the moved options from the source
                selectedOptions.forEach(option => {
                    if (fromSelect === selectedRoles) {
                        // Find and enable the corresponding option in available
                        const availableOption = Array.from(availableRoles.options)
                            .find(o => o.value === option.value);
                        if (availableOption) {
                            availableOption.disabled = false;
                        }
                    }
                    fromSelect.remove(option.index);
                });
                
                // Sort options alphabetically
                sortSelectOptions(toSelect);
            }
            
            // Sort select options alphabetically
            function sortSelectOptions(selectElement) {
                const options = Array.from(selectElement.options);
                options.sort((a, b) => a.text.localeCompare(b.text));
                
                // Remove all current options
                while (selectElement.options.length > 0) {
                    selectElement.remove(0);
                }
                
                // Add sorted options back
                options.forEach(option => {
                    selectElement.add(option);
                });
            }

            // Update hidden inputs based on selected roles
            function updateHiddenInputs() {
                // Clear existing inputs
                hiddenContainer.innerHTML = '';
                
                // Create new hidden inputs for each selected role
                Array.from(selectedRoles.options).forEach(option => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'roles[]';
                    input.value = option.value;
                    hiddenContainer.appendChild(input);
                });
            }
            
            // Initialize with sorted lists
            sortSelectOptions(availableRoles);
            sortSelectOptions(selectedRoles);
        });
    </script>
    @endpush
</x-app-layout>