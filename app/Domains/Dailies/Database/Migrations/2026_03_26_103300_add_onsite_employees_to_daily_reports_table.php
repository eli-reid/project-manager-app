<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_reports')) {
            return;
        }

        if (! Schema::hasColumn('daily_reports', 'onsite_employees')) {
            Schema::table('daily_reports', function (Blueprint $table): void {
                $table->json('onsite_employees')->nullable()->after('visitors');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_reports')) {
            return;
        }

        if (Schema::hasColumn('daily_reports', 'onsite_employees')) {
            Schema::table('daily_reports', function (Blueprint $table): void {
                $table->dropColumn('onsite_employees');
            });
        }
    }
};
