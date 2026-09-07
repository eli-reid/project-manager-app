<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_annotation_comments')) {
            return;
        }

        Schema::create('plan_annotation_comments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_annotation_id')->constrained('plan_annotations')->cascadeOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_annotation_comments');
    }
};
