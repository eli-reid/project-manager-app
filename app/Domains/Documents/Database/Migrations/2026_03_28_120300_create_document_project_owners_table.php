<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Deprecated: ownership pivots replaced by documents.owner_scope + documents.owner_id.
    }

    public function down(): void
    {
        // No-op.
    }
};
