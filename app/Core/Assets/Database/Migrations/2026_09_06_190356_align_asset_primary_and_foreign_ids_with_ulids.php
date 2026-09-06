<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets') || ! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = DB::getDatabaseName();
        $userIdColumn = DB::selectOne(
            'SELECT CHARACTER_SET_NAME AS character_set, COLLATION_NAME AS collation_name
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$database, 'users', 'id'],
        );

        if ($userIdColumn === null) {
            throw new RuntimeException('Unable to inspect users.id while repairing asset identifiers.');
        }

        $characterSet = $this->validatedIdentifier($userIdColumn->character_set);
        $collation = $this->validatedIdentifier($userIdColumn->collation_name);
        $foreignKeys = DB::select(
            'SELECT kcu.TABLE_NAME AS table_name,
                kcu.COLUMN_NAME AS column_name,
                kcu.CONSTRAINT_NAME AS constraint_name,
                rc.DELETE_RULE AS delete_rule,
                rc.UPDATE_RULE AS update_rule
            FROM information_schema.KEY_COLUMN_USAGE kcu
            INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE kcu.TABLE_SCHEMA = ?
                AND kcu.REFERENCED_TABLE_NAME = ?
                AND kcu.REFERENCED_COLUMN_NAME = ?',
            [$database, 'assets', 'id'],
        );

        foreach ($foreignKeys as $foreignKey) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $this->validatedIdentifier($foreignKey->table_name),
                $this->validatedIdentifier($foreignKey->constraint_name),
            ));
        }

        DB::statement(sprintf(
            'ALTER TABLE `assets` MODIFY `id` CHAR(26) CHARACTER SET %s COLLATE %s NOT NULL',
            $characterSet,
            $collation,
        ));

        $assetIdColumns = DB::select(
            'SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name, IS_NULLABLE AS is_nullable
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ? AND TABLE_NAME <> ?',
            [$database, 'asset_id', 'assets'],
        );

        foreach ($assetIdColumns as $assetIdColumn) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` CHAR(26) CHARACTER SET %s COLLATE %s %s',
                $this->validatedIdentifier($assetIdColumn->table_name),
                $this->validatedIdentifier($assetIdColumn->column_name),
                $characterSet,
                $collation,
                $assetIdColumn->is_nullable === 'YES' ? 'NULL' : 'NOT NULL',
            ));
        }

        foreach ($foreignKeys as $foreignKey) {
            DB::statement(sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `assets` (`id`) ON DELETE %s ON UPDATE %s',
                $this->validatedIdentifier($foreignKey->table_name),
                $this->validatedIdentifier($foreignKey->constraint_name),
                $this->validatedIdentifier($foreignKey->column_name),
                $this->validatedForeignKeyAction($foreignKey->delete_rule),
                $this->validatedForeignKeyAction($foreignKey->update_rule),
            ));
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Asset identifiers cannot be safely converted back to integers.');
    }

    private function validatedIdentifier(string $identifier): string
    {
        if (preg_match('/^[a-zA-Z0-9_]+$/', $identifier) !== 1) {
            throw new RuntimeException('Unsafe database identifier encountered while repairing asset identifiers.');
        }

        return $identifier;
    }

    private function validatedForeignKeyAction(string $action): string
    {
        $action = strtoupper($action);

        if (! in_array($action, ['CASCADE', 'NO ACTION', 'RESTRICT', 'SET DEFAULT', 'SET NULL'], true)) {
            throw new RuntimeException('Unsupported foreign key action encountered while repairing asset identifiers.');
        }

        return $action;
    }
};
