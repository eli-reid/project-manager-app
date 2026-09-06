<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'created_by_id')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->dropCreatorForeignKeys();

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $userIdColumn = DB::selectOne(
                'SELECT CHARACTER_SET_NAME, COLLATION_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [DB::getDatabaseName(), 'users', 'id'],
            );

            if ($userIdColumn === null
                || preg_match('/^[a-zA-Z0-9_]+$/', $userIdColumn->CHARACTER_SET_NAME) !== 1
                || preg_match('/^[a-zA-Z0-9_]+$/', $userIdColumn->COLLATION_NAME) !== 1) {
                throw new RuntimeException('Unable to determine a safe users.id character set and collation.');
            }

            DB::statement(sprintf(
                'ALTER TABLE `assets` MODIFY `created_by_id` VARCHAR(255) CHARACTER SET %s COLLATE %s NULL',
                $userIdColumn->CHARACTER_SET_NAME,
                $userIdColumn->COLLATION_NAME,
            ));
        } else {
            Schema::table('assets', function (Blueprint $table): void {
                $table->string('created_by_id')->nullable()->change();
            });
        }

        DB::table('assets')
            ->whereNotNull('created_by_id')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', 'assets.created_by_id');
            })
            ->update(['created_by_id' => null]);

        if (isset($userIdColumn)) {
            DB::statement(sprintf(
                'ALTER TABLE `assets` MODIFY `created_by_id` CHAR(26) CHARACTER SET %s COLLATE %s NULL',
                $userIdColumn->CHARACTER_SET_NAME,
                $userIdColumn->COLLATION_NAME,
            ));
        } else {
            Schema::table('assets', function (Blueprint $table): void {
                $table->char('created_by_id', 26)->nullable()->change();
            });
        }

        Schema::table('assets', function (Blueprint $table): void {
            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('assets')
            || ! Schema::hasColumn('assets', 'created_by_id')
            || Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->dropCreatorForeignKeys();

        Schema::table('assets', function (Blueprint $table): void {
            $table->string('created_by_id')->nullable()->change();
        });
    }

    private function dropCreatorForeignKeys(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('assets'))
            ->filter(fn (array $foreignKey): bool => $foreignKey['columns'] === ['created_by_id']);

        foreach ($foreignKeys as $foreignKey) {
            Schema::table('assets', function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign($foreignKey['name']);
            });
        }
    }
};
