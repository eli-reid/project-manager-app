<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('submittals')) {
            Schema::create('submittals', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('project_id');
                $table->string('type');
                $table->string('spec_reference')->nullable();
                $table->string('vendor')->nullable();
                $table->date('need_by_date')->nullable();
                $table->string('status');
                $table->ulid('submitted_by_id');
                $table->ulid('current_reviewer_id')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('distributed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('project_id')->references('id')->on('projects');
                $table->foreign('submitted_by_id')->references('id')->on('users');
                $table->foreign('current_reviewer_id')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submittals');
    }
};
