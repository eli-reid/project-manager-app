<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_tab_user_preferences') && ! Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
            Schema::table('project_tab_user_preferences', function (Blueprint $table): void {
                // Add a nullable project_id column. Use a plain string/ulid column to
                // avoid cross-DB ALTER TABLE foreign key issues in test environments.
                $table->string('project_id')->nullable()->after('tab_key')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_tab_user_preferences') && Schema::hasColumn('project_tab_user_preferences', 'project_id')) {
            Schema::table('project_tab_user_preferences', function (Blueprint $table): void {
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            });
        }
    }
};
