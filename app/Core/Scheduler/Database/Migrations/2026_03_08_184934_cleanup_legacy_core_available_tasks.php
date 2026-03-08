<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('available_tasks') || ! Schema::hasTable('scheduled_tasks')) {
            return;
        }

        $legacyCoreFeatureTypes = [
            'timecard_reminders',
            'automated_reports',
            'database_backup',
            'system_cleanup',
        ];

        DB::table('available_tasks')
            ->whereIn('feature_type', $legacyCoreFeatureTypes)
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw('1'))
                    ->from('scheduled_tasks')
                    ->whereColumn('scheduled_tasks.available_task_id', 'available_tasks.id');
            })
            ->delete();
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
