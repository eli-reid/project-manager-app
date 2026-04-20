<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('timecard_required_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->unique();
            $table->boolean('reminders_enabled')->default(true);
            $table->dateTime('effective_start_date')->nullable();
            $table->dateTime('effective_end_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timecard_required_users');
    }
};
