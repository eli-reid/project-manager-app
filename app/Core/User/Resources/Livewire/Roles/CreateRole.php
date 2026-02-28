<?php

namespace App\Core\User\Resources\Livewire\Roles;

use Livewire\Component;
use App\Core\User\Models\Role;
use Illuminate\Support\Facades\Session;

class CreateRole extends Component
{
    protected $listeners = [
        'assignedPermissionsUpdated' => 'handleAssignedPermissionsUpdated',
    ];

    public function handleAssignedPermissionsUpdated($permissions)
    {
        $this->selectedPermissions = $permissions;
    }

    public $name = '';
    public $description = '';
    public $selectedPermissions = [];

    public $allPermissions = [];

    public $permissionsByResource = [];

    public function mount()
    {
        // Load all permissions for selection
        $this->allPermissions = \App\Core\User\Models\Permission::all();

        // Build permissions grouped by resource for selector
        $grouped = [];
        foreach ($this->allPermissions as $perm) {
            $resource = $perm->resource;
            if (!isset($grouped[$resource])) {
                $grouped[$resource] = [
                    'name' => ucwords(str_replace(['_', '-'], ' ', $resource)),
                    'permissions' => [],
                ];
            }
            $grouped[$resource]['permissions'][$perm->action] = $perm->label ?? "{$perm->resource}.{$perm->action}";
        }
        $this->permissionsByResource = $grouped;
    }

    protected $rules = [
        'name' => 'required|string|max:255|unique:roles,name',
        'description' => 'nullable|string|max:255',
        'selectedPermissions' => 'array',
        'selectedPermissions.*' => 'exists:permissions,id',
    ];

    public function submit()
    {
        $this->validate();

        $role = Role::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        if (!empty($this->selectedPermissions)) {
            // selectedPermissions from selector are strings like resource.action; need to map to permission IDs
            $permissionIds = \App\Core\User\Models\Permission::whereIn('resource', collect($this->selectedPermissions)->map(function($p){
                return explode('.', $p, 2)[0];
            }))->get()->filter(function($perm) {
                return in_array($perm->resource . '.' . $perm->action, $this->selectedPermissions);
            })->pluck('id')->toArray();

            $role->permissions()->sync($permissionIds);
        }

        Session::flash('success', 'Role created successfully!');
        $this->reset(['name', 'description', 'selectedPermissions']);
    }

    public function render()
    {
        return view('core.user.resources.livewire.roles.create-role', [
            'permissions' => $this->allPermissions,
        ]);
    }
}
