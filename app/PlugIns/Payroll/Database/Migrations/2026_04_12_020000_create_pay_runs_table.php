<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pay_runs')) {
            Schema::create('pay_runs', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->date('pay_period_start');
                $table->date('pay_period_end');
                $table->date('pay_date');
                $table->string('status')->default('draft');
                $table->decimal('total_gross', 14, 2)->default(0);
                $table->decimal('total_net', 14, 2)->default(0);
                $table->decimal('total_taxes', 14, 2)->default(0);
                $table->unsignedInteger('employee_count')->default(0);
                $table->foreignUlid('created_by')->constrained('users');
                $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['pay_period_start', 'pay_period_end']);
                $table->index(['status', 'pay_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pay_runs')) {
            Schema::dropIfExists('pay_runs');
        }
    }
};
