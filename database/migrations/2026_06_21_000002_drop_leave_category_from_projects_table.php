<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'leave_category')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('leave_category');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'leave_category')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->string('leave_category')->nullable();
            });
        }
    }
};
