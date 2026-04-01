<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_job_history', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->uuid('job_uuid')->nullable()->index();
            $table->string('job_class');
            $table->string('queue')->default('default')->index();
            $table->string('connection')->default('database');
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('status')->default('running')->index();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->longText('exception')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_job_history');
    }
};
