<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->boolean('is_prevailing_wage')->default(false)->after('budget');
            $table->string('wage_determination_id', 30)->nullable()->after('is_prevailing_wage');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['is_prevailing_wage', 'wage_determination_id']);
        });
    }
};
