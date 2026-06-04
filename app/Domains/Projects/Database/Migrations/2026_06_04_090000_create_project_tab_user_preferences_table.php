<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_tab_user_preferences')) {
            Schema::create('project_tab_user_preferences', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('tab_key');
                $table->unsignedInteger('sort_order')->default(100);
                $table->boolean('is_hidden')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'tab_key']);
                $table->index(['user_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tab_user_preferences');
    }
};
