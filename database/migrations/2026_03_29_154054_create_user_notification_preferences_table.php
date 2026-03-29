<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_notification_preferences')) {
            Schema::create('user_notification_preferences', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('notification_key');
                $table->string('channel');
                $table->boolean('enabled')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'notification_key', 'channel'], 'user_notification_preferences_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_notification_preferences')) {
            Schema::dropIfExists('user_notification_preferences');
        }
    }
};
