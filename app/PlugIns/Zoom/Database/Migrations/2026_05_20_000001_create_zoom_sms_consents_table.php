<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('zoom_sms_consents')) {
            Schema::create('zoom_sms_consents', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('phone_number')->unique();
                $table->string('status')->default('pending');
                $table->timestamp('consent_requested_at')->nullable();
                $table->timestamp('consented_at')->nullable();
                $table->timestamp('declined_at')->nullable();
                $table->timestamps();

                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('zoom_sms_consents')) {
            Schema::dropIfExists('zoom_sms_consents');
        }
    }
};
