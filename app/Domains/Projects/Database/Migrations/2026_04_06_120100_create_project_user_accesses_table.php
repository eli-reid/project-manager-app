<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_user_accesses')) {
            Schema::create('project_user_accesses', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUlid('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('permission_keys')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'user_id']);
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_user_accesses');
    }
};
