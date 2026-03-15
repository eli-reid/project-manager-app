<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_categories')) {
            Schema::create('task_categories', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignUlid('parent_id')->nullable()->constrained('task_categories')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['project_id', 'parent_id', 'sort_order']);
                $table->index(['is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignUlid('task_category_id')->nullable()->constrained('task_categories')->nullOnDelete();
                $table->foreignUlid('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('todo');
                $table->string('priority')->default('medium');
                $table->decimal('estimated_hours', 8, 2)->nullable();
                $table->unsignedTinyInteger('completion_percentage')->default(0);
                $table->date('due_date')->nullable();
                $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_billable')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['project_id', 'status']);
                $table->index(['project_id', 'task_category_id']);
                $table->index(['project_id', 'parent_task_id', 'sort_order']);
                $table->index(['assigned_to', 'status']);
            });
        }

        if (! Schema::hasTable('task_templates')) {
            Schema::create('task_templates', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignUlid('task_category_id')->nullable()->constrained('task_categories')->nullOnDelete();
                $table->string('priority')->default('medium');
                $table->decimal('estimated_hours', 8, 2)->nullable();
                $table->boolean('is_billable')->default(true);
                $table->json('template_tasks')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('task_templates')) {
            Schema::dropIfExists('task_templates');
        }

        if (Schema::hasTable('tasks')) {
            Schema::dropIfExists('tasks');
        }

        if (Schema::hasTable('task_categories')) {
            Schema::dropIfExists('task_categories');
        }
    }
};
