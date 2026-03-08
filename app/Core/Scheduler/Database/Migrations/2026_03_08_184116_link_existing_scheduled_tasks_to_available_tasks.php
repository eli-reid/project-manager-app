<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('available_tasks')) {
            Schema::create('available_tasks', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('feature_type')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('task_config')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('scheduled_tasks')) {
            return;
        }

        if (! Schema::hasColumn('scheduled_tasks', 'available_task_id')) {
            Schema::table('scheduled_tasks', function (Blueprint $table): void {
                $table->foreignUlid('available_task_id')
                    ->nullable()
                    ->after('description')
                    ->constrained('available_tasks')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('scheduled_tasks', 'feature_type')) {
            return;
        }

        $featureTypes = DB::table('scheduled_tasks')
            ->select('feature_type')
            ->whereNotNull('feature_type')
            ->where('feature_type', '<>', '')
            ->distinct()
            ->pluck('feature_type');

        foreach ($featureTypes as $featureType) {
            $existingTask = DB::table('available_tasks')
                ->where('feature_type', $featureType)
                ->first();

            $availableTaskId = $existingTask?->id;

            if ($availableTaskId === null) {
                $availableTaskId = (string) Str::ulid();

                DB::table('available_tasks')->insert([
                    'id' => $availableTaskId,
                    'feature_type' => $featureType,
                    'name' => str((string) $featureType)->replace(['.', '_', '-'], ' ')->headline()->value(),
                    'description' => null,
                    'task_config' => json_encode([]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('scheduled_tasks')
                ->where('feature_type', $featureType)
                ->whereNull('available_task_id')
                ->update([
                    'available_task_id' => $availableTaskId,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
