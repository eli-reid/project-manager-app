<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('submittal_approvals')) {
            Schema::create('submittal_approvals', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('submittal_id');
                $table->unsignedInteger('step');
                $table->ulid('reviewer_id');
                $table->string('status');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('submittal_id')->references('id')->on('submittals');
                $table->foreign('reviewer_id')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submittal_approvals');
    }
};
