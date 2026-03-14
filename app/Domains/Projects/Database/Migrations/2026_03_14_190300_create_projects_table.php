<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->string('project_number')->nullable()->unique();
                $table->text('description')->nullable();
                $table->string('status')->default('pending');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignUlid('address_id')->nullable()->constrained('addresses')->nullOnDelete();
                $table->foreignUlid('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::dropIfExists('projects');
        }
    }
};
