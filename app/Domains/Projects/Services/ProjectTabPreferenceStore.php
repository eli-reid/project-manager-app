<?php

namespace App\Domains\Projects\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\ProjectTabUserPreference;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProjectTabPreferenceStore
{
    private ?bool $hasProjectTabUserPreferencesTable = null;

    /**
     * @param  array<int, string>  $tabKeys
     * @return Collection<string, ProjectTabUserPreference>
     */
    public function loadPreferences(User $user, array $tabKeys, ?Project $project = null): Collection
    {
        if ($tabKeys === [] || ! $this->tableExists()) {
            return collect();
        }

        $query = ProjectTabUserPreference::query()
            ->where('user_id', $user->id)
            ->whereIn('tab_key', $tabKeys);

        // If the preferences table has a project_id column, allow project-specific
        // preferences to exist alongside global (null project_id) preferences.
        if ($project !== null && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
            $query->where(function ($q) use ($project) {
                $q->whereNull('project_id')
                    ->orWhere('project_id', $project->id);
            });
        }

        $rows = $query->get();

        // If project-specific rows exist for a tab_key prefer them over global ones.
        $map = collect();
        foreach ($rows as $row) {
            $key = $row->tab_key;
            if (! $map->has($key)) {
                $map->put($key, $row);
                continue;
            }

            // prefer the project-specific row when available
            if ($project !== null && isset($row->project_id) && $row->project_id === $project->id) {
                $map->put($key, $row);
            }
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $visibleKeys
     * @param  array<int, string>  $hiddenKeys
     */
    public function persist(User $user, array $visibleKeys, array $hiddenKeys, ?Project $project = null): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $rows = [];
        $sortOrder = 1;
        $timestamp = now();
        foreach ($visibleKeys as $tabKey) {
            $row = [
                'user_id' => $user->id,
                'tab_key' => $tabKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if ($project !== null && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
                $row['project_id'] = $project->id;
            }

            $rows[] = $row;
        }

        foreach ($hiddenKeys as $tabKey) {
            $row = [
                'user_id' => $user->id,
                'tab_key' => $tabKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if ($project !== null && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
                $row['project_id'] = $project->id;
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            return;
        }

        // Use a composite unique key if the project_id column exists to avoid
        // clobbering project-scoped preferences with global ones.
        $uniqueBy = ['user_id', 'tab_key'];
        if ($project !== null && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
            $uniqueBy[] = 'project_id';
        }

        ProjectTabUserPreference::query()->upsert(
            $rows,
            $uniqueBy,
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
