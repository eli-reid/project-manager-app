<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installed_plugins', function (Blueprint $table): void {
            if (! Schema::hasColumn('installed_plugins', 'trust_level')) {
                $table->string('trust_level', 40)->default('reviewed_third_party')->after('source_type');
            }

            if (! Schema::hasColumn('installed_plugins', 'execution_mode')) {
                $table->string('execution_mode', 40)->default('in_process_limited')->after('trust_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('installed_plugins', function (Blueprint $table): void {
            if (Schema::hasColumn('installed_plugins', 'execution_mode')) {
                $table->dropColumn('execution_mode');
            }

            if (Schema::hasColumn('installed_plugins', 'trust_level')) {
                $table->dropColumn('trust_level');
            }
        });
    }
};
