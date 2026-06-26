<?php

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('change_orders')) {
            Schema::create('change_orders', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default(ChangeOrder::STATUS_DRAFT);
                $table->decimal('labor_amount', 12, 2)->default(0);
                $table->decimal('materials_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->foreignUlid('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUlid('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUlid('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('implemented_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('client_approved_at')->nullable();
                $table->string('client_approval_reference')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['project_id', 'status']);
                $table->index(['status', 'submitted_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('change_orders')) {
            Schema::dropIfExists('change_orders');
        }
    }
};
