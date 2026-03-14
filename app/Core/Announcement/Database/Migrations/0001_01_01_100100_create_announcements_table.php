<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('title');
                $table->text('content');
                $table->string('type')->default('general');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_dismissable')->default(false);
                $table->dateTime('start_date')->nullable();
                $table->dateTime('end_date')->nullable();
                $table->foreignUlid('created_by')->constrained('users');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'start_date', 'end_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('announcements')) {
            Schema::dropIfExists('announcements');
        }
    }
};
