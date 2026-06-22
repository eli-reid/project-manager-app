<?php

namespace App\Domains\Projects\Services;

use App\Core\Settings\Facades\Settings;
use App\Domains\Projects\Models\Project;

class ProjectNumber
{
    public static function getNext(): string
    {
        $prefix = Settings::get('projects.number_prefix', 'PRJ-')->toString();
        $highestSequence = self::highestSequenceForPrefix($prefix);
        $nextSequence = $highestSequence + 1;

        $candidate = self::formatProjectNumber($prefix, $nextSequence);

        while (Project::query()->where('project_number', $candidate)->exists()) {
            $nextSequence++;
            $candidate = self::formatProjectNumber($prefix, $nextSequence);
        }

        return $candidate;
    }

    protected static function highestSequenceForPrefix(string $prefix): int
    {
        return Project::query()
            ->whereNotNull('project_number')
            ->when($prefix !== '', fn ($q) => $q->where('project_number', 'like', $prefix.'%'))
            ->pluck('project_number')
            ->map(fn (mixed $n): int => is_string($n) ? (int) substr($n, strlen($prefix)) : 0)
            ->max() ?? 0;
    }

    protected static function formatProjectNumber(string $prefix, int $sequence): string
    {
        return $prefix.str_pad((string) $sequence, (int) Settings::get('projects.number_length', 4), '0', STR_PAD_LEFT);
    }
}
