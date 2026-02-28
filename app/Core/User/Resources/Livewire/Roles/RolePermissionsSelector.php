<?php

namespace App\Core\User\Resources\Livewire\Roles;

use Livewire\Component;

class RolePermissionsSelector extends Component
{
    public $permissionsByResource = [];
    public $assignedPermissions = [];
    public $selectedAvailable = [];
    public $selectedAssigned = [];
    public $resourceFilter = 'all';
    public $searchTerm = '';

    public function mount($permissionsByResource = [], $assignedPermissions = [])
    {
        $this->permissionsByResource = $permissionsByResource;
        $this->assignedPermissions = $assignedPermissions;
    }

    public function updatedResourceFilter()
    {
        // No-op, just for reactivity
    }

    public function updatedSearchTerm()
    {
        // No-op, just for reactivity
    }

    public function addPermission($permId)
    {
        if (!in_array($permId, $this->assignedPermissions)) {
            $this->assignedPermissions[] = $permId;
            $this->emitUp('assignedPermissionsUpdated', $this->assignedPermissions);
        }
    }

    public function removePermission($permId)
    {
        $this->assignedPermissions = array_filter($this->assignedPermissions, function($id) use ($permId) {
            return $id !== $permId;
        });
        $this->emitUp('assignedPermissionsUpdated', $this->assignedPermissions);
    }

    public function toggleAvailableSelection($permId)
    {
        if (in_array($permId, $this->selectedAvailable)) {
            $this->selectedAvailable = array_values(array_diff($this->selectedAvailable, [$permId]));
        } else {
            $this->selectedAvailable[] = $permId;
        }
    }

    public function toggleAssignedSelection($permId)
    {
        if (in_array($permId, $this->selectedAssigned)) {
            $this->selectedAssigned = array_values(array_diff($this->selectedAssigned, [$permId]));
        } else {
            $this->selectedAssigned[] = $permId;
        }
    }

    public function addSelectedPermissions()
    {
        if (empty($this->selectedAvailable)) {
            return;
        }

        foreach ($this->selectedAvailable as $permId) {
            if (!in_array($permId, $this->assignedPermissions)) {
                $this->assignedPermissions[] = $permId;
            }
        }

        $this->selectedAvailable = [];
        $this->emitUp('assignedPermissionsUpdated', $this->assignedPermissions);
    }

    public function removeSelectedPermissions()
    {
        if (empty($this->selectedAssigned)) {
            return;
        }

        $this->assignedPermissions = array_values(array_diff($this->assignedPermissions, $this->selectedAssigned));
        $this->selectedAssigned = [];
        $this->emitUp('assignedPermissionsUpdated', $this->assignedPermissions);
    }

    public function addAll()
    {
        // reuse render logic to get available list
        $filtered = [];
        foreach ($this->permissionsByResource as $resourceKey => $resourceData) {
            if ($this->resourceFilter === 'all' || $this->resourceFilter === $resourceKey) {
                foreach ($resourceData['permissions'] as $permissionKey => $permissionLabel) {
                    $permId = $resourceKey . '.' . $permissionKey;
                    if (!in_array($permId, $this->assignedPermissions)) {
                        if ($this->searchTerm === '' || stripos($permissionLabel, $this->searchTerm) !== false || stripos($resourceData['name'], $this->searchTerm) !== false) {
                            $filtered[] = $permId;
                        }
                    }
                }
            }
        }

        foreach ($filtered as $permId) {
            if (!in_array($permId, $this->assignedPermissions)) {
                $this->assignedPermissions[] = $permId;
            }
        }

        $this->emitUp('assignedPermissionsUpdated', $this->assignedPermissions);
    }

    public function removeAll()
    {
        $this->assignedPermissions = [];
        $this->selectedAssigned = [];
        $this->emitUp('assignedPermissionsUpdated', $this->assignedPermissions);
    }

    public function render()
    {
        $filteredAvailable = [];
        foreach ($this->permissionsByResource as $resourceKey => $resourceData) {
            if ($this->resourceFilter === 'all' || $this->resourceFilter === $resourceKey) {
                foreach ($resourceData['permissions'] as $permissionKey => $permissionLabel) {
                    $permId = $resourceKey . '.' . $permissionKey;
                    if (!in_array($permId, $this->assignedPermissions)) {
                        if ($this->searchTerm === '' || stripos($permissionLabel, $this->searchTerm) !== false || stripos($resourceData['name'], $this->searchTerm) !== false) {
                            $filteredAvailable[] = [
                                'id' => $permId,
                                'label' => $permissionLabel,
                                'resource' => $resourceKey,
                                'resourceLabel' => $resourceData['name'],
                            ];
                        }
                    }
                }
            }
        }
        $assigned = [];
        foreach ($this->assignedPermissions as $permId) {
            [$resourceKey, $permissionKey] = explode('.', $permId, 2);
            if (isset($this->permissionsByResource[$resourceKey]['permissions'][$permissionKey])) {
                $assigned[] = [
                    'id' => $permId,
                    'label' => $this->permissionsByResource[$resourceKey]['permissions'][$permissionKey],
                    'resource' => $resourceKey,
                    'resourceLabel' => $this->permissionsByResource[$resourceKey]['name'],
                ];
            }
        }
        return view('core.user.resources.views.components.role-permissions-selector', [
            'availablePermissions' => $filteredAvailable,
            'assignedPermissions' => $assigned,
            'resources' => $this->permissionsByResource,
        ]);
    }
}
