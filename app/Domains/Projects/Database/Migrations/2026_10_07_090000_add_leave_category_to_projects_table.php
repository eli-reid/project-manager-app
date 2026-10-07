<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill the leave_category column for databases whose projects table was created
     * before it was added to the guarded create_projects_table migration.
     */
    public function up(): void
    {
        if (Schema::hasTable('projects') && ! Schema::hasColumn('projects', 'leave_category')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->string('leave_category')->nullable();
            });
        }
    }

    /**
     * The column is owned by create_projects_table on fresh databases, so it is not dropped here.
     */
    public function down(): void
    {
        //
    }
};
