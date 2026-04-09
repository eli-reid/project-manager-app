<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_role_accesses')) {
            Schema::create('project_role_accesses', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignUlid('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('permission_keys')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'role_id']);
                $table->index('role_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_role_accesses');
    }
};
