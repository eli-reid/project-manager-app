<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('submittal_documents')) {
            return;
        }

        Schema::table('submittal_documents', function (Blueprint $table): void {
            if (! Schema::hasColumn('submittal_documents', 'document_role')) {
                $table->string('document_role', 50)->default('reference')->after('document_id');
            }

            if (! Schema::hasColumn('submittal_documents', 'document_status')) {
                $table->string('document_status', 40)->default('active')->after('document_role');
            }

            if (! Schema::hasColumn('submittal_documents', 'revision')) {
                $table->string('revision', 40)->nullable()->after('document_status');
            }

            if (! Schema::hasColumn('submittal_documents', 'discipline')) {
                $table->string('discipline', 60)->nullable()->after('revision');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('submittal_documents')) {
            return;
        }

        Schema::table('submittal_documents', function (Blueprint $table): void {
            if (Schema::hasColumn('submittal_documents', 'discipline')) {
                $table->dropColumn('discipline');
            }

            if (Schema::hasColumn('submittal_documents', 'revision')) {
                $table->dropColumn('revision');
            }

            if (Schema::hasColumn('submittal_documents', 'document_status')) {
                $table->dropColumn('document_status');
            }

            if (Schema::hasColumn('submittal_documents', 'document_role')) {
                $table->dropColumn('document_role');
            }
        });
    }
};
