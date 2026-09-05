<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_references')) {
            $this->addAssetForeignKeyIfMissing();

            return;
        }

        $assetIdCollation = $this->assetIdCollation();

        Schema::create('asset_references', function (Blueprint $table) use ($assetIdCollation): void {
            $table->bigIncrements('id');
            $assetId = $table->ulid('asset_id');

            if ($assetIdCollation !== null) {
                $assetId->charset($assetIdCollation->CHARACTER_SET_NAME)
                    ->collation($assetIdCollation->COLLATION_NAME);
            }

            $table->string('referencer_type', 60);
            $table->string('referencer_id', 60);
            $table->string('role', 40)->default('primary');
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['asset_id', 'referencer_type', 'referencer_id', 'role'],
                'asset_references_unique_edge'
            );
            $table->index(['referencer_type', 'referencer_id'], 'asset_references_referencer_index');

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_references');
    }

    private function addAssetForeignKeyIfMissing(): void
    {
        $hasAssetForeignKey = collect(Schema::getForeignKeys('asset_references'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['foreign_table'] === 'assets'
                && $foreignKey['columns'] === ['asset_id']);

        if ($hasAssetForeignKey) {
            return;
        }

        $assetIdCollation = $this->assetIdCollation();

        if ($assetIdCollation !== null) {
            DB::statement(sprintf(
                'ALTER TABLE `asset_references` MODIFY `asset_id` CHAR(26) CHARACTER SET %s COLLATE %s NOT NULL',
                $assetIdCollation->CHARACTER_SET_NAME,
                $assetIdCollation->COLLATION_NAME,
            ));
        }

        Schema::table('asset_references', function (Blueprint $table): void {
            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
        });
    }

    private function assetIdCollation(): ?object
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return null;
        }

        return DB::selectOne(
            'SELECT CHARACTER_SET_NAME, COLLATION_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), 'assets', 'id'],
        );
    }
};
