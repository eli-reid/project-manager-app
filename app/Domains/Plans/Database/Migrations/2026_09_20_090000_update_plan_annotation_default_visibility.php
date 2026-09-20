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
        // Intentionally does not revert `public` rows back to `project`: that would be
        // destructive to any annotation created (or explicitly set to `public`) after this
        // migration ran, not just the rows this migration originally converted. Only the
        // schema default is reversed.
        Schema::table('plan_annotations', function (Blueprint $table): void {
            $table->string('visibility')->default('project')->change();
        });
    }
};
