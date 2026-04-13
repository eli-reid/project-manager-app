<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && ! Schema::hasColumn('projects', 'pay_rate_type_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->foreignUlid('pay_rate_type_id')
                    ->nullable()
                    ->after('wage_determination_id')
                    ->constrained('pay_rate_types')
                    ->nullOnDelete();

                $table->index('pay_rate_type_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'pay_rate_type_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('pay_rate_type_id');
            });
        }
    }
};
