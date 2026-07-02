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

        $baseQuery = ProjectTabUserPreference::query()
            ->where('user_id', $user->id);

        // If the preferences table has a project_id column, allow project-specific
        // preferences to exist alongside global (null project_id) preferences.
        if ($project !== null && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
            $rows = $baseQuery
                ->whereIn('tab_key', $tabKeys)
                ->where(function ($q) use ($project) {
                    $q->whereNull('project_id')
                        ->orWhere('project_id', $project->id);
                })
                ->get();

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

        // Backward-compatibility fallback: when the DB schema does not yet have a
        // `project_id` column, we may have stored project-scoped preferences by
        // prefixing the `tab_key` (e.g. "project:{projectId}:{tabKey}"). Query
        // for both the base keys and prefixed keys so older fallbacks continue to
        // work until the migration is applied.
        if ($project !== null) {
            $prefixed = array_map(fn ($k) => 'project:'.$project->id.':'.$k, $tabKeys);

            $rows = $baseQuery
                ->where(function ($q) use ($tabKeys, $prefixed) {
                    $q->whereIn('tab_key', $tabKeys)
                        ->orWhereIn('tab_key', $prefixed);
                })
                ->get();

            $map = collect();
            $prefix = 'project:'.$project->id.':';

            foreach ($rows as $row) {
                $raw = $row->tab_key;
                $baseKey = str_starts_with($raw, $prefix) ? substr($raw, strlen($prefix)) : $raw;

                if (! $map->has($baseKey)) {
                    $map->put($baseKey, $row);
                    continue;
                }

                // prefer the prefixed (project-scoped) row over the global one
                if (str_starts_with($raw, $prefix)) {
                    $map->put($baseKey, $row);
                }
            }

            \Illuminate\Support\Facades\Log::debug('Loaded project tab preferences (prefixed-fallback)', [
                'user_id' => $user->id,
                'project_id' => $project->id ?? null,
                'requested_keys' => $tabKeys,
                'rows' => collect($rows)->map(fn($r) => [
                    'raw_tab_key' => $r->tab_key,
                    'tab_key' => (str_starts_with($r->tab_key, $prefix) ? substr($r->tab_key, strlen($prefix)) : $r->tab_key),
                    'sort_order' => $r->sort_order,
                    'is_hidden' => $r->is_hidden ?? null,
                ])->all(),
                'mapped' => $map->map(fn($r) => [
                    'tab_key' => $r->tab_key,
                    'sort_order' => $r->sort_order,
                    'is_hidden' => $r->is_hidden ?? null,
                    'project_id' => $r->project_id ?? null,
                ])->all(),
            ]);

            return $map;
        }

        // No project context: just load global preferences for the provided keys.
        $rows = $baseQuery
            ->whereIn('tab_key', $tabKeys)
            ->get();

        $map = collect();
        foreach ($rows as $row) {
            $map->put($row->tab_key, $row);
        }

        \Illuminate\Support\Facades\Log::debug('Loaded project tab preferences (global)', [
            'user_id' => $user->id,
            'requested_keys' => $tabKeys,
            'rows' => $map->map(fn($r) => [
                'tab_key' => $r->tab_key,
                'sort_order' => $r->sort_order,
                'is_hidden' => $r->is_hidden ?? null,
                'project_id' => $r->project_id ?? null,
            ])->all(),
        ]);

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

        // Determine whether we should use the project_id column or a
        // prefixed-tab-key fallback (for environments where the migration
        // hasn't been applied yet).
        $usePrefixedTabKey = $project !== null && ! Schema::hasColumn('project_tab_user_preferences', 'project_id');
        $prefix = $usePrefixedTabKey ? ('project:'.$project->id.':') : null;

        foreach ($visibleKeys as $tabKey) {
            $storedKey = $usePrefixedTabKey ? $prefix.$tabKey : $tabKey;

            $row = [
                'user_id' => $user->id,
                'tab_key' => $storedKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (! $usePrefixedTabKey && $project !== null && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
                $row['project_id'] = $project->id;
            }

            $rows[] = $row;
        }

        foreach ($hiddenKeys as $tabKey) {
            $storedKey = $usePrefixedTabKey ? $prefix.$tabKey : $tabKey;

            $row = [
                'user_id' => $user->id,
                'tab_key' => $storedKey,
                'sort_order' => $sortOrder++,
                'is_hidden' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (! $usePrefixedTabKey && $project !== null && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
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

        try {
            ProjectTabUserPreference::query()->upsert(
                $rows,
                $uniqueBy,
                ['sort_order', 'is_hidden', 'updated_at'],
            );

            // Read back the persisted rows for debugging to confirm the DB state.
            $tabKeys = array_values(array_unique(array_map(fn($r) => $r['tab_key'], $rows)));
            $persistedRows = ProjectTabUserPreference::query()
                ->where('user_id', $user->id)
                ->whereIn('tab_key', $tabKeys)
                ->get();

            $persisted = $persistedRows
                ->map(function ($r) use ($prefix) {
                    $raw = $r->tab_key;
                    $baseKey = ($prefix !== null && str_starts_with($raw, $prefix)) ? substr($raw, strlen($prefix)) : $raw;

                    return [
                        'tab_key' => $baseKey,
                        'raw_tab_key' => $raw,
                        'sort_order' => $r->sort_order,
                        'is_hidden' => $r->is_hidden,
                        'project_id' => $r->project_id ?? null,
                    ];
                })
                ->all();

            \Illuminate\Support\Facades\Log::debug('Persisted project tab user preferences', [
                'user_id' => $user->id,
                'project_id' => $project?->id ?? null,
                'persisted' => $persisted,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to persist project tab user preferences', [
                'user_id' => $user->id,
                'project_id' => $project?->id ?? null,
                'rows' => $rows,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
