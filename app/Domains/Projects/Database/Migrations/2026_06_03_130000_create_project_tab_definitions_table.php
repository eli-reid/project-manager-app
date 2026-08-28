<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_tab_definitions')) {
            Schema::create('project_tab_definitions', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->string('label');
                $table->string('mode_query_param')->nullable();
                $table->unsignedInteger('sort_order')->default(100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });

            $now = now();
            DB::table('project_tab_definitions')->insert([
                ['key' => 'overview', 'label' => 'Overview', 'mode_query_param' => null, 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'dailies', 'label' => 'Dailies', 'mode_query_param' => null, 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'tasks', 'label' => 'Tasks', 'mode_query_param' => null, 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'invoices', 'label' => 'Invoices', 'mode_query_param' => 'invoiceMode', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'stock', 'label' => 'Stock', 'mode_query_param' => null, 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'submittals', 'label' => 'Submittals', 'mode_query_param' => 'submittalMode', 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'change-orders', 'label' => 'Change Orders', 'mode_query_param' => 'changeOrderMode', 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'rfis', 'label' => 'RFIs', 'mode_query_param' => 'rfiMode', 'sort_order' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'documents', 'label' => 'Library', 'mode_query_param' => null, 'sort_order' => 90, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'access', 'label' => 'Access', 'mode_query_param' => null, 'sort_order' => 100, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'time', 'label' => 'Time', 'mode_query_param' => null, 'sort_order' => 110, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['key' => 'financials', 'label' => 'Financials', 'mode_query_param' => null, 'sort_order' => 120, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tab_definitions');
    }
};
