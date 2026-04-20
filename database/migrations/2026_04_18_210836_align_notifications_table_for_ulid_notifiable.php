<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasColumn('notifications', 'id')) {
            DB::statement('ALTER TABLE notifications MODIFY COLUMN id CHAR(26) NOT NULL');
        }

        if (Schema::hasColumn('notifications', 'notifiable_id')) {
            DB::statement('ALTER TABLE notifications MODIFY COLUMN notifiable_id CHAR(26) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasColumn('notifications', 'id')) {
            DB::statement('ALTER TABLE notifications MODIFY COLUMN id CHAR(36) NOT NULL');
        }

        if (Schema::hasColumn('notifications', 'notifiable_id')) {
            DB::statement('ALTER TABLE notifications MODIFY COLUMN notifiable_id VARCHAR(255) NOT NULL');
        }
    }
};
