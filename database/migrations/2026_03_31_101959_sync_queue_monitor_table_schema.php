<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use romanzipp\QueueMonitor\Enums\MonitorStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('queue_monitor')) {
            return;
        }

        Schema::table('queue_monitor', function (Blueprint $table) {
            if (! Schema::hasColumn('queue_monitor', 'job_uuid')) {
                $table->uuid('job_uuid')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'job_id')) {
                $table->string('job_id')->nullable()->index();
            }

            if (! Schema::hasColumn('queue_monitor', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'queue')) {
                $table->string('queue')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'status')) {
                $table->unsignedInteger('status')->default(MonitorStatus::RUNNING);
            }

            if (! Schema::hasColumn('queue_monitor', 'queued_at')) {
                $table->dateTime('queued_at')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'started_at')) {
                $table->timestamp('started_at')->nullable()->index();
            }

            if (! Schema::hasColumn('queue_monitor', 'started_at_exact')) {
                $table->string('started_at_exact')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'finished_at')) {
                $table->timestamp('finished_at')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'finished_at_exact')) {
                $table->string('finished_at_exact')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'attempt')) {
                $table->integer('attempt')->default(0);
            }

            if (! Schema::hasColumn('queue_monitor', 'retried')) {
                $table->boolean('retried')->default(false);
            }

            if (! Schema::hasColumn('queue_monitor', 'progress')) {
                $table->integer('progress')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'exception')) {
                $table->longText('exception')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'exception_message')) {
                $table->text('exception_message')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'exception_class')) {
                $table->text('exception_class')->nullable();
            }

            if (! Schema::hasColumn('queue_monitor', 'data')) {
                $table->longText('data')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('queue_monitor')) {
            return;
        }

        Schema::table('queue_monitor', function (Blueprint $table) {
            $columns = [
                'job_uuid',
                'job_id',
                'name',
                'queue',
                'status',
                'queued_at',
                'started_at',
                'started_at_exact',
                'finished_at',
                'finished_at_exact',
                'attempt',
                'retried',
                'progress',
                'exception',
                'exception_message',
                'exception_class',
                'data',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('queue_monitor', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
