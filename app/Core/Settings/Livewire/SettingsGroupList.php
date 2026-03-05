<?php

namespace App\Core\Settings\Livewire;

use App\Core\Settings\Models\SettingsSqlite;
use Livewire\Component;
use Livewire\Attributes\Computed;

/**
 * SettingsGroupList Component
 * 
 * Displays a list of all available settings groups with counts.
 * Allows selecting a group to edit settings within it.
 */
class SettingsGroupList extends Component
{
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
        $this->selectedGroup = $this->settingGroups()->first()?->group ?? '';
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
     * Get all unique setting groups
     */
    #[Computed]
    public function settingGroups()
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
     * Get count of settings in a group
     */
    public function getGroupCount(string $group): int
    {
        return SettingsSqlite::where('group', $group)
            ->where('is_visible', true)
            ->count();
    }

    /**
     * Get display name for group (capitalized)
     */
    public function getGroupDisplayName(string $group): string
    {
        return ucfirst(str_replace('_', ' ', $group));
    }

    public function render()
    {
        return view('core::livewire.settings-group-list', [
            'groups' => $this->settingGroups,
            'selectedGroup' => $this->selectedGroup,
        ]);
    }
}
