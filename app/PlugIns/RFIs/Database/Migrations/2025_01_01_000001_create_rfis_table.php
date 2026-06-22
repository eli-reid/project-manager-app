<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfis', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('status')->default('draft');
            $table->foreignUlid('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('answered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('answer')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->decimal('cost_impact', 10, 2)->nullable();
            $table->integer('schedule_impact_days')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfis');
    }
};
