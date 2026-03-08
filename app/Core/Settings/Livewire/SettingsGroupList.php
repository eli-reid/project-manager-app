<?php

namespace App\Core\Settings\Livewire;

use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * SettingsGroupList Component
 *
 * Displays a list of all available settings groups.
 * Allows selecting a group to edit settings within it.
 */
class SettingsGroupList extends Component
{
    use AuthorizesRequests;

    /**
     * Selected setting group
     */
    public string $selectedGroup = '';

    /**
     * Search/filter term
     */
    public string $search = '';

    /**
     * Mount component with default group
     */
    public function mount(): void
    {
        $this->authorize('viewAny', SettingsSqlite::class);

        $this->selectedGroup = $this->settingGroups->first()?->group ?? '';

        if ($this->selectedGroup !== '') {
            $this->dispatch('group-selected', group: $this->selectedGroup);
        }
    }

    /**
     * Select a group to edit
     */
    public function selectGroup(string $group): void
    {
        $this->selectedGroup = $group;
        $this->dispatch('group-selected', group: $group);
    }

    /**
     * Keep editor in sync when group is changed by model binding (mobile select).
     */
    public function updatedSelectedGroup(string $group): void
    {
        $this->dispatch('group-selected', group: $group);
    }

    /**
     * Get all unique setting groups
     */
    #[Computed]
    public function settingGroups(): Collection
    {
        return SettingsSqlite::query()
            ->select('group')
            ->distinct()
            ->whereNotNull('group')
            ->where('is_visible', true)
            ->orderBy('group')
            ->get();
    }

    /**
     * Get display name for group (capitalized)
     */
    public function getGroupDisplayName(string $group): string
    {
        return ucfirst(str_replace('_', ' ', $group));
    }

    public function render(): View
    {
        return view('core::livewire.settings-group-list', [
            'groups' => $this->settingGroups,
            'selectedGroup' => $this->selectedGroup,
        ]);
    }
}
