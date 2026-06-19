<?php

namespace App\Domains\Projects\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\ProjectTabUserPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProjectTabPreferenceStore
{
    private ?bool $hasProjectTabUserPreferencesTable = null;

    /**
     * @param  array<int, string>  $tabKeys
     * @return Collection<string, ProjectTabUserPreference>
     */
    public function loadPreferences(User $user, array $tabKeys): Collection
    {
        if ($tabKeys === [] || ! $this->tableExists()) {
            return collect();
        }

        return ProjectTabUserPreference::query()
            ->where('user_id', $user->id)
            ->whereIn('tab_key', $tabKeys)
            ->get()
            ->keyBy('tab_key');
    }

    /**
     * @param  array<int, string>  $visibleKeys
     * @param  array<int, string>  $hiddenKeys
     */
    public function persist(User $user, array $visibleKeys, array $hiddenKeys): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $rows = [];
        $sortOrder = 1;
        $timestamp = now();

        foreach ($visibleKeys as $tabKey) {
            $rows[] = [
                'user_id' => $user->id,
                'tab_key' => $tabKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        foreach ($hiddenKeys as $tabKey) {
            $rows[] = [
                'user_id' => $user->id,
                'tab_key' => $tabKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if ($rows === []) {
            return;
        }

        ProjectTabUserPreference::query()->upsert(
            $rows,
            ['user_id', 'tab_key'],
            ['sort_order', 'is_hidden', 'updated_at'],
        );
    }

    private function tableExists(): bool
    {
        if (is_bool($this->hasProjectTabUserPreferencesTable)) {
            return $this->hasProjectTabUserPreferencesTable;
        }

        $this->hasProjectTabUserPreferencesTable = Schema::hasTable('project_tab_user_preferences');

        return $this->hasProjectTabUserPreferencesTable;
    }
}
