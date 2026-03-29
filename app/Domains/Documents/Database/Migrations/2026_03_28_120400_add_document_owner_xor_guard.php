<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_user_owners') || ! Schema::hasTable('document_project_owners')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_user_owners_xor_insert
                BEFORE INSERT ON document_user_owners
                FOR EACH ROW
                WHEN EXISTS (
                    SELECT 1
                    FROM document_project_owners
                    WHERE document_id = NEW.document_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'documents ownership XOR violation: document already has a project owner');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_project_owners_xor_insert
                BEFORE INSERT ON document_project_owners
                FOR EACH ROW
                WHEN EXISTS (
                    SELECT 1
                    FROM document_user_owners
                    WHERE document_id = NEW.document_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'documents ownership XOR violation: document already has a user owner');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_user_owners_xor_update
                BEFORE UPDATE OF document_id ON document_user_owners
                FOR EACH ROW
                WHEN EXISTS (
                    SELECT 1
                    FROM document_project_owners
                    WHERE document_id = NEW.document_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'documents ownership XOR violation: document already has a project owner');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_project_owners_xor_update
                BEFORE UPDATE OF document_id ON document_project_owners
                FOR EACH ROW
                WHEN EXISTS (
                    SELECT 1
                    FROM document_user_owners
                    WHERE document_id = NEW.document_id
                )
                BEGIN
                    SELECT RAISE(ABORT, 'documents ownership XOR violation: document already has a user owner');
                END;
            SQL);

            return;
        }

        if ($driver === 'mysql') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_user_owners_xor_insert
                BEFORE INSERT ON document_user_owners
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1
                        FROM document_project_owners
                        WHERE document_id = NEW.document_id
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'documents ownership XOR violation: document already has a project owner';
                    END IF;
                END
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_project_owners_xor_insert
                BEFORE INSERT ON document_project_owners
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1
                        FROM document_user_owners
                        WHERE document_id = NEW.document_id
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'documents ownership XOR violation: document already has a user owner';
                    END IF;
                END
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_user_owners_xor_update
                BEFORE UPDATE ON document_user_owners
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1
                        FROM document_project_owners
                        WHERE document_id = NEW.document_id
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'documents ownership XOR violation: document already has a project owner';
                    END IF;
                END
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_project_owners_xor_update
                BEFORE UPDATE ON document_project_owners
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1
                        FROM document_user_owners
                        WHERE document_id = NEW.document_id
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'documents ownership XOR violation: document already has a user owner';
                    END IF;
                END
            SQL);

            return;
        }

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION enforce_document_ownership_xor()
                RETURNS trigger
                LANGUAGE plpgsql
                AS $$
                BEGIN
                    IF TG_TABLE_NAME = 'document_user_owners' THEN
                        IF EXISTS (
                            SELECT 1
                            FROM document_project_owners
                            WHERE document_id = NEW.document_id
                        ) THEN
                            RAISE EXCEPTION 'documents ownership XOR violation: document already has a project owner';
                        END IF;
                    ELSE
                        IF EXISTS (
                            SELECT 1
                            FROM document_user_owners
                            WHERE document_id = NEW.document_id
                        ) THEN
                            RAISE EXCEPTION 'documents ownership XOR violation: document already has a user owner';
                        END IF;
                    END IF;

                    RETURN NEW;
                END;
                $$;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_user_owners_xor_insert
                BEFORE INSERT OR UPDATE ON document_user_owners
                FOR EACH ROW
                EXECUTE FUNCTION enforce_document_ownership_xor();
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER document_project_owners_xor_insert
                BEFORE INSERT OR UPDATE ON document_project_owners
                FOR EACH ROW
                EXECUTE FUNCTION enforce_document_ownership_xor();
            SQL);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_user_owners') || ! Schema::hasTable('document_project_owners')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite' || $driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS document_user_owners_xor_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS document_project_owners_xor_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS document_user_owners_xor_update');
            DB::unprepared('DROP TRIGGER IF EXISTS document_project_owners_xor_update');

            return;
        }

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS document_user_owners_xor_insert ON document_user_owners');
            DB::unprepared('DROP TRIGGER IF EXISTS document_project_owners_xor_insert ON document_project_owners');
            DB::unprepared('DROP FUNCTION IF EXISTS enforce_document_ownership_xor()');
        }
    }
};
