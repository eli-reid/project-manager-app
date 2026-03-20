<?php

use App\Domains\Timecards\Models\Timecard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('timecards')) {
            Schema::create('timecards', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('user_id')->constrained('users');
                $table->date('week_starting');
                $table->date('week_ending');
                $table->string('status')->default(Timecard::STATUS_DRAFT);
                $table->decimal('total_hours', 8, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->foreignUlid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['user_id', 'week_starting']);
                $table->index(['status', 'week_starting']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('timecards')) {
            Schema::dropIfExists('timecards');
        }
    }
};
