<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only create if table doesn't exist
        if (!Schema::connection('settings_sqlite')->hasTable('settings')) {
            Schema::connection('settings_sqlite')->create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->longText('default_value')->nullable();
                $table->string('display_name')->nullable();
                $table->text('description')->nullable();
                $table->string('type')->default('text'); // text, email, password, number, integer, boolean, select, textarea, url, array, json
                $table->string('group')->default('general')->index();
                $table->text('options')->nullable(); // JSON array for select options
                $table->integer('order')->default(0);
                $table->boolean('is_public')->default(false);
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_required')->default(false);
                $table->boolean('encrypted')->default(false);
                $table->timestamps();

                // Indexes for performance
                $table->index('group');
                $table->index(['group', 'key']);
                $table->index('is_visible');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('settings_sqlite')->dropIfExists('settings');
    }
};
