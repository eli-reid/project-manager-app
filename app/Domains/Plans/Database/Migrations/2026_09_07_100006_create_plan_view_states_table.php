<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_view_states')) {
            return;
        }

        Schema::create('plan_view_states', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('plan_sheet_id')->constrained('plan_sheets')->cascadeOnDelete();
            $table->decimal('zoom', 8, 4);
            $table->decimal('center_x', 8, 6);
            $table->decimal('center_y', 8, 6);
            $table->timestamp('last_viewed_at');
            $table->timestamps();
            $table->unique(['user_id', 'plan_sheet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_view_states');
    }
};
