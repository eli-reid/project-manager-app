<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plan_annotations')->where('visibility', 'project')->update(['visibility' => 'public']);

        Schema::table('plan_annotations', function (Blueprint $table): void {
            $table->string('visibility')->default('public')->change();
        });
    }

    public function down(): void
    {
        Schema::table('plan_annotations', function (Blueprint $table): void {
            $table->string('visibility')->default('project')->change();
        });

        DB::table('plan_annotations')->where('visibility', 'public')->update(['visibility' => 'project']);
    }
};
