<div>
    <form wire:submit.prevent="submit">
        <div>
            <label for="name">Role Name</label>
            <input type="text" id="name" wire:model.lazy="name">
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="description">Description</label>
            <input type="text" id="description" wire:model.lazy="description">
            @error('description') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="permissions">Assign Permissions</label>
            @livewire(\App\Core\User\Resources\Livewire\Roles\RolePermissionsSelector::class, [
                'permissionsByResource' => $permissionsByResource ?? [],
                'assignedPermissions' => $selectedPermissions ?? []
            ])
            @error('selectedPermissions') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <button type="submit">Create Role</button>
        @if (session()->has('success'))
            <div class="text-success">{{ session('success') }}</div>
        @endif
    </form>
</div>