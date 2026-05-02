<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_user_dismissals')) {
            Schema::create('announcement_user_dismissals', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('announcement_id')->constrained('announcements')->cascadeOnDelete();
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('dismissed_at');
                $table->timestamps();

                $table->unique(['announcement_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('announcement_user_dismissals')) {
            Schema::dropIfExists('announcement_user_dismissals');
        }
    }
};
