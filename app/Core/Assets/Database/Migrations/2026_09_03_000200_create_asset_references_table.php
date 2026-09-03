<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_references')) {
            return;
        }

        Schema::create('asset_references', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('asset_id');
            $table->string('referencer_type', 60);
            $table->string('referencer_id', 60);
            $table->string('role', 40)->default('primary');
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['asset_id', 'referencer_type', 'referencer_id', 'role'],
                'asset_references_unique_edge'
            );
            $table->index(['referencer_type', 'referencer_id'], 'asset_references_referencer_index');

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_references');
    }
};
