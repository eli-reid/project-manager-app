<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && ! Schema::hasColumn('projects', 'accounting_code')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->string('accounting_code')->nullable()->after('project_number');
                $table->index('accounting_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'accounting_code')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropIndex(['accounting_code']);
                $table->dropColumn('accounting_code');
            });
        }
    }
};
