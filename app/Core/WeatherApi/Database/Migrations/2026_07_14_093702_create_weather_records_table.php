<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('weather_records')) {
            Schema::create('weather_records', function (Blueprint $table): void {
                $table->id();
                $table->string('location_key');
                $table->string('source_location');
                $table->string('location_name')->nullable();
                $table->string('record_type', 20);
                $table->date('weather_date');
                $table->decimal('temperature', 8, 2)->nullable();
                $table->decimal('temperature_high', 8, 2)->nullable();
                $table->decimal('temperature_low', 8, 2)->nullable();
                $table->string('temperature_unit', 10)->default('F');
                $table->decimal('wind_speed', 8, 2)->nullable();
                $table->string('wind_direction', 20)->nullable();
                $table->decimal('precipitation', 8, 2)->nullable();
                $table->unsignedSmallInteger('humidity')->nullable();
                $table->string('condition_text')->nullable();
                $table->string('weather_icon')->nullable();
                $table->timestamp('synced_at');
                $table->timestamps();

                $table->unique(['location_key', 'record_type', 'weather_date'], 'weather_records_unique_snapshot');
                $table->index(['location_key', 'record_type', 'weather_date'], 'weather_records_lookup_index');
                $table->index('synced_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('weather_records')) {
            Schema::dropIfExists('weather_records');
        }
    }
};
