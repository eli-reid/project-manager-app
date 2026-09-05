<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_shares')) {
            Schema::create('asset_shares', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->ulid('asset_id');
                $table->string('token')->unique();
                $table->timestamp('expires_at')->nullable()->index();
                $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('asset_id');
                $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_shares');
    }
};
