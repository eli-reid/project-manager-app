<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Role Details -->
                        <div>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Role Details</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Basic information about the role.
                            </p>

                            <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-4">
                                    <x-input-label for="name" value="Role Name" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>
                                
                                <div class="sm:col-span-6">
                                    <x-input-label for="description" value="Description (Optional)" />
                                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm">{{ old('description') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <x-input-label for="access_level" value="Access Level (0-100)" />
                                    <x-text-input id="access_level" name="access_level" type="number" min="0" max="100" class="mt-1 block w-full" value="{{ old('access_level', 0) }}" required />
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Higher numbers mean more access. Admin is 100.
                                    </p>
                                    <x-input-error class="mt-2" :messages="$errors->get('access_level')" />
                                </div>
                                
                                <div class="sm:col-span-3">
                                    <div class="flex items-start mt-6">
                                        <div class="flex items-center h-5">
                                            <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" {{ old('is_active') ? 'checked' : 'checked' }}>
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="is_active" class="font-medium text-gray-700 dark:text-gray-300">Active</label>
                                            <p class="text-gray-500 dark:text-gray-400">Make this role available for assignment to users.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permission Selection -->
                        <div class="pt-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Permissions</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Select permissions to assign to this role.
                            </p>
                            
                            <div class="mt-6" x-data="{
                                availablePermissions: {},
                                assignedPermissions: [],
                                selectedAvailable: [],
                                selectedAssigned: [],
                                resourceFilter: 'all',
                                searchTerm: '',
                                
                                init() {
                                    // Initialize permissions by resource
                                    @foreach($permissionsByResource as $resourceKey => $resourceData)
                                        this.availablePermissions['{{ $resourceKey }}'] = [];
                                        @foreach($resourceData['permissions'] as $permissionKey => $permissionLabel)
                                            this.availablePermissions['{{ $resourceKey }}'].push({
                                                id: '{{ $resourceKey }}.{{ $permissionKey }}',
                                                label: '{{ $permissionLabel }}',
                                                resource: '{{ $resourceKey }}',
                                                resourceLabel: '{{ $resourceData['name'] }}'
                                            });
                                        @endforeach
                                    @endforeach
                                    
                                    // Initialize with old values if validation failed
                                    const oldPermissions = @json(old('permissions', []));
                                    if (oldPermissions.length > 0) {
                                        // Add pre-selected permissions to assigned list
                                        oldPermissions.forEach(permId => {
                                            for (const resource in this.availablePermissions) {
                                                const permIndex = this.availablePermissions[resource].findIndex(p => p.id === permId);
                                                if (permIndex !== -1) {
                                                    this.assignedPermissions.push(this.availablePermissions[resource][permIndex]);
                                                    this.availablePermissions[resource].splice(permIndex, 1);
                                                }
                                            }
                                        });
                                    }
                                },
                                
                                filteredAvailablePermissions() {
                                    let result = [];
                                    
                                    // Filter by selected resource or show all
                                    for (const resource in this.availablePermissions) {
                                        if (this.resourceFilter === 'all' || this.resourceFilter === resource) {
                                            result = result.concat(this.availablePermissions[resource]);
                                        }
                                    }
                                    
                                    // Apply search filter
                                    if (this.searchTerm.trim() !== '') {
                                        const search = this.searchTerm.toLowerCase();
                                        result = result.filter(p => 
                                            p.label.toLowerCase().includes(search) || 
                                            p.resourceLabel.toLowerCase().includes(search) ||
                                            p.id.toLowerCase().includes(search)
                                        );
                                    }
                                    
                                    return result;
                                },
                                
                                addSelectedPermissions() {
                                    if (this.selectedAvailable.length === 0) return;
                                    
                                    this.selectedAvailable.forEach(permId => {
                                        for (const resource in this.availablePermissions) {
                                            const permIndex = this.availablePermissions[resource].findIndex(p => p.id === permId);
                                            if (permIndex !== -1) {
                                                this.assignedPermissions.push(this.availablePermissions[resource][permIndex]);
                                                this.availablePermissions[resource].splice(permIndex, 1);
                                            }
                                        }
                                    });
                                    
                                    this.selectedAvailable = [];
                                },
                                
                                removeSelectedPermissions() {
                                    if (this.selectedAssigned.length === 0) return;
                                    
                                    this.selectedAssigned.forEach(permId => {
                                        const permIndex = this.assignedPermissions.findIndex(p => p.id === permId);
                                        if (permIndex !== -1) {
                                            const perm = this.assignedPermissions[permIndex];
                                            if (!this.availablePermissions[perm.resource]) {
                                                this.availablePermissions[perm.resource] = [];
                                            }
                                            this.availablePermissions[perm.resource].push(perm);
                                            this.assignedPermissions.splice(permIndex, 1);
                                        }
                                    });
                                    
                                    this.selectedAssigned = [];
                                },
                                
                                toggleAvailable(permId) {
                                    const index = this.selectedAvailable.indexOf(permId);
                                    if (index === -1) {
                                        this.selectedAvailable.push(permId);
                                    } else {
                                        this.selectedAvailable.splice(index, 1);
                                    }
                                },
                                
                                toggleAssigned(permId) {
                                    const index = this.selectedAssigned.indexOf(permId);
                                    if (index === -1) {
                                        this.selectedAssigned.push(permId);
                                    } else {
                                        this.selectedAssigned.splice(index, 1);
                                    }
                                },
                                
                                addAll() {
                                    const filtered = this.filteredAvailablePermissions();
                                    filtered.forEach(perm => {
                                        this.assignedPermissions.push(perm);
                                        
                                        for (const resource in this.availablePermissions) {
                                            const permIndex = this.availablePermissions[resource].findIndex(p => p.id === perm.id);
                                            if (permIndex !== -1) {
                                                this.availablePermissions[resource].splice(permIndex, 1);
                                            }
                                        }
                                    });
                                },
                                
                                removeAll() {
                                    this.assignedPermissions.forEach(perm => {
                                        if (!this.availablePermissions[perm.resource]) {
                                            this.availablePermissions[perm.resource] = [];
                                        }
                                        this.availablePermissions[perm.resource].push(perm);
                                    });
                                    this.assignedPermissions = [];
                                }
                            }">
                                <!-- Filter controls -->
                                <div class="flex flex-col sm:flex-row justify-between mb-4 gap-4">
                                    <div class="flex-1">
                                        <x-input-label for="resource-filter" :value="__('Filter by Resource')" />
                                        <select id="resource-filter" x-model="resourceFilter" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm">
                                            <option value="all">All Resources</option>
                                            @foreach($permissionsByResource as $resourceKey => $resourceData)
                                                <option value="{{ $resourceKey }}">{{ $resourceData['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <x-input-label for="search" :value="__('Search Permissions')" />
                                        <x-text-input id="search" x-model="searchTerm" type="text" class="mt-1 block w-full" placeholder="Type to search..." />
                                    </div>
                                </div>
                                
                                <!-- Dual list boxes with proper spacing -->
                                <div class="grid grid-cols-1 md:grid-cols-2 md:gap-12 gap-6">
                                    <!-- Available permissions - adjusted width to accommodate icons -->
                                    <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm">
                                        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600 rounded-t-md">
                                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Available Permissions
                                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400" x-text="'(' + filteredAvailablePermissions().length + ')'"></span>
                                            </h3>
                                        </div>
                                        
                                        <div class="max-h-96 overflow-y-auto p-1">
                                            <template x-for="perm in filteredAvailablePermissions()" :key="perm.id">
                                                <div 
                                                    class="flex items-center p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                                    :class="{ 'bg-indigo-50 dark:bg-indigo-900': selectedAvailable.includes(perm.id) }"
                                                    @click="toggleAvailable(perm.id)">
                                                    
                                                    <div class="flex-1">
                                                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100" x-text="perm.label"></div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="perm.resourceLabel"></div>
                                                    </div>
                                                    
                                                    <div class="flex-shrink-0">
                                                        <span 
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize"
                                                            :class="{
                                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': perm.resource === 'projects',
                                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': perm.resource === 'users',
                                                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': perm.resource === 'clients',
                                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': perm.resource === 'timecards',
                                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': perm.resource === 'global',
                                                                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300': perm.resource === 'settings',
                                                                'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300': !['projects', 'users', 'clients', 'timecards', 'global', 'settings'].includes(perm.resource)
                                                            }"
                                                            x-text="perm.resource"></span>
                                                    </div>
                                                </div>
                                            </template>
                                            
                                            <!-- Empty state -->
                                            <div
                                                x-show="filteredAvailablePermissions().length === 0"
                                                class="py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                                No available permissions match your criteria
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Transfer buttons for mobile view -->
                                    <div class="flex md:hidden items-center justify-center my-4 space-x-2">
                                        <button type="button" @click="addSelectedPermissions" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                            <span class="ml-1">Add</span>
                                        </button>
                                        
                                        <button type="button" @click="removeSelectedPermissions" class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                            </svg>
                                            <span class="ml-1">Remove</span>
                                        </button>
                                    </div>
                                    
                                    <!-- Assigned permissions with properly positioned transfer buttons -->
                                    <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm md:relative md:top-0 md:left-1 md:right-1">
                                        <!-- Transfer buttons for desktop - repositioned to prevent overlap -->
                                        <div class="hidden md:flex absolute left-0 top-1/2 transform -translate-x-full -translate-y-1/2">
                                            <div class="flex flex-col space-y-2 mr-4">
                                                <!-- Add selected - right arrow -->
                                                <button type="button" @click="addSelectedPermissions" class="inline-flex items-center justify-center p-1 w-8 h-8 bg-indigo-600 border border-transparent rounded-full text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Add selected permissions">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                                
                                                <!-- Add all - double right arrow -->
                                                <button type="button" @click="addAll" class="inline-flex items-center justify-center p-1 w-8 h-8 bg-indigo-600 border border-transparent rounded-full text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Add all filtered permissions">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                                
                                                <!-- Remove selected - left arrow -->
                                                <button type="button" @click="removeSelectedPermissions" class="inline-flex items-center justify-center p-1 w-8 h-8 bg-gray-600 border border-transparent rounded-full text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500" title="Remove selected permissions">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </button>
                                                
                                                <!-- Remove all - double left arrow -->
                                                <button type="button" @click="removeAll" class="inline-flex items-center justify-center p-1 w-8 h-8 bg-gray-600 border border-transparent rounded-full text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500" title="Remove all assigned permissions">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7M19 19l-7-7 7-7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600 rounded-t-md">
                                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Assigned Permissions
                                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400" x-text="'(' + assignedPermissions.length + ')'"></span>
                                            </h3>
                                        </div>
                                        
                                        <div class="max-h-96 overflow-y-auto p-1">
                                            <template x-for="perm in assignedPermissions" :key="perm.id">
                                                <div 
                                                    class="flex items-center p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                                    :class="{ 'bg-indigo-50 dark:bg-indigo-900': selectedAssigned.includes(perm.id) }"
                                                    @click="toggleAssigned(perm.id)">
                                                    
                                                    <div class="flex-1">
                                                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100" x-text="perm.label"></div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="perm.resourceLabel"></div>
                                                    </div>
                                                    
                                                    <div class="flex-shrink-0">
                                                        <span 
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize"
                                                            :class="{
                                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': perm.resource === 'projects',
                                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': perm.resource === 'users',
                                                                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': perm.resource === 'clients',
                                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': perm.resource === 'timecards',
                                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': perm.resource === 'global',
                                                                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300': perm.resource === 'settings',
                                                                'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300': !['projects', 'users', 'clients', 'timecards', 'global', 'settings'].includes(perm.resource)
                                                            }"
                                                            x-text="perm.resource"></span>
                                                    </div>
                                                </div>
                                            </template>
                                            
                                            <!-- Empty state -->
                                            <div
                                                x-show="assignedPermissions.length === 0"
                                                class="py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                                No permissions assigned yet
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden inputs to submit assigned permissions -->
                                <div>
                                    <template x-for="perm in assignedPermissions" :key="perm.id">
                                        <input type="hidden" name="permissions[]" :value="perm.id">
                                    </template>
                                </div>
                            </div>
                            
                            <x-input-error class="mt-2" :messages="$errors->get('permissions')" />
                        </div>

                        <div class="pt-6 flex justify-end">
                            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 mr-2">
                                Cancel
                            </a>
                            <x-primary-button>
                                Create Role
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>