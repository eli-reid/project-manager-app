<div>
    <div class="flex flex-col sm:flex-row justify-between mb-4 gap-4">
        <div class="flex-1">
            <label for="resource-filter">Filter by Resource</label>
            <select id="resource-filter" wire:model="resourceFilter" class="mt-1 block w-full rounded-md border-gray-300">
                <option value="all">All Resources</option>
                @foreach($resources as $resourceKey => $resourceData)
                    <option value="{{ $resourceKey }}">{{ $resourceData['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <label for="search">Search Permissions</label>
            <input id="search" wire:model.debounce.300ms="searchTerm" type="text" class="mt-1 block w-full" placeholder="Type to search..." />
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 md:gap-12 gap-6">
        <div class="bg-white border border-gray-300 rounded-md shadow-sm">
            <div class="p-4 bg-gray-50 border-b border-gray-300 rounded-t-md">
                <h3 class="text-sm font-medium text-gray-700">
                    Available Permissions
                    <span class="ml-1 text-xs text-gray-500">({{ count($availablePermissions) }})</span>
                </h3>
            </div>
            <div class="max-h-96 overflow-y-auto p-1">
                @forelse($availablePermissions as $perm)
                    <div class="flex items-center p-2 rounded-md hover:bg-gray-100 cursor-pointer">
                        <div class="flex items-center">
                            <input type="checkbox" wire:click="toggleAvailableSelection('{{ $perm['id'] }}')" @if(in_array($perm['id'], $selectedAvailable)) checked @endif class="mr-3">
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm text-gray-900">{{ $perm['label'] }}</div>
                            <div class="text-xs text-gray-500">{{ $perm['resourceLabel'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-500 text-sm">
                        No available permissions match your criteria
                    </div>
                @endforelse
            </div>
        </div>
        <div class="bg-white border border-gray-300 rounded-md shadow-sm">
            <div class="p-4 bg-gray-50 border-b border-gray-300 rounded-t-md">
                <h3 class="text-sm font-medium text-gray-700">
                    Assigned Permissions
                    <span class="ml-1 text-xs text-gray-500">({{ count($assignedPermissions) }})</span>
                </h3>
            </div>
            <div class="max-h-96 overflow-y-auto p-1">
                @forelse($assignedPermissions as $perm)
                    <div class="flex items-center p-2 rounded-md hover:bg-gray-100 cursor-pointer">
                        <div class="flex items-center">
                            <input type="checkbox" wire:click="toggleAssignedSelection('{{ $perm['id'] }}')" @if(in_array($perm['id'], $selectedAssigned)) checked @endif class="mr-3">
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm text-gray-900">{{ $perm['label'] }}</div>
                            <div class="text-xs text-gray-500">{{ $perm['resourceLabel'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-500 text-sm">
                        No assigned permissions
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="mt-4 flex items-center space-x-2">
        <button type="button" wire:click="addSelectedPermissions" class="px-3 py-1 bg-indigo-600 text-white rounded">Add Selected</button>
        <button type="button" wire:click="removeSelectedPermissions" class="px-3 py-1 bg-gray-600 text-white rounded">Remove Selected</button>
        <button type="button" wire:click="addAll" class="px-3 py-1 bg-indigo-600 text-white rounded">Add All</button>
        <button type="button" wire:click="removeAll" class="px-3 py-1 bg-gray-600 text-white rounded">Remove All</button>
    </div>
    <div>
        @foreach($assignedPermissions as $perm)
            <input type="hidden" name="permissions[]" value="{{ $perm['id'] }}">
        @endforeach
    </div>
</div>
