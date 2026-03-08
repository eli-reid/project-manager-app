<?php

namespace App\Livewire\Admin\Roles;

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Role Form')]
class Form extends Component
{
    public ?Role $role = null;

    public bool $isEdit = false;

    public string $name = '';

    public string $description = '';

    public int $access_level = 10;

    public bool $is_active = true;

    /**
     * @var array<int, string>
     */
    public array $selectedPermissionIds = [];

    public function mount(?Role $role = null): void
    {
        if ($role !== null && $role->exists) {
            $this->role = $role;
            $this->isEdit = true;
            $this->name = $role->name;
            $this->description = (string) $role->description;
            $this->access_level = (int) $role->access_level;
            $this->is_active = (bool) $role->is_active;
            $this->selectedPermissionIds = $role->permissions()->pluck('permissions.id')->all();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($this->role?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'access_level' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'selectedPermissionIds' => ['array'],
            'selectedPermissionIds.*' => ['exists:permissions,id'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->isEdit && $this->role?->built_in && ! $validated['is_active']) {
            $this->addError('is_active', 'Built-in roles cannot be disabled.');

            return;
        }

        if ($this->isEdit) {
            $role = $this->role;

            if ($role === null) {
                return;
            }

            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?: null,
                'access_level' => $role->built_in ? $role->access_level : $validated['access_level'],
                'is_active' => $role->built_in ? true : (bool) $validated['is_active'],
            ]);
        } else {
            $role = Role::query()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?: null,
                'access_level' => $validated['access_level'],
                'is_active' => (bool) $validated['is_active'],
                'built_in' => false,
            ]);
        }

        $role->permissions()->sync($validated['selectedPermissionIds']);

        session()->flash('success', $this->isEdit ? 'Role updated successfully.' : 'Role created successfully.');

        $this->redirectRoute('admin.roles.index', navigate: true);
    }

    #[Computed]
    public function permissionsByResource(): array
    {
        $permissions = Permission::query()->orderBy('resource')->orderBy('action')->get();

        $grouped = [];

        foreach ($permissions as $permission) {
            if (! isset($grouped[$permission->resource])) {
                $grouped[$permission->resource] = [
                    'name' => str($permission->resource)->replace(['_', '-'], ' ')->headline()->value(),
                    'permissions' => [],
                ];
            }

            $grouped[$permission->resource]['permissions'][] = $permission;
        }

        return $grouped;
    }

    public function render()
    {
        return view('livewire.admin.roles.form', [
            'permissionsByResource' => $this->permissionsByResource,
        ])->layout('layouts.app', [
            'title' => $this->isEdit ? 'Edit Role' : 'Create Role',
        ]);
    }
}
