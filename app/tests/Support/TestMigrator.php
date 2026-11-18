<?php
declare(strict_types=1);

namespace App\Test\Support;

use Cake\Datasource\ConnectionManager;
use Migrations\TestSuite\Migrator;

/**
 * Migrator that also drops the functions of the `public` schema when it rebuilds the test database.
 *
 * The stock Migrator drops tables only. The row-level security design keeps its helpers, definer functions and
 * trigger functions in the schema, so a rebuild that left them behind would fail on the first CREATE FUNCTION.
 * It must run on the migration (owner) connection, which owns every one of them.
 */
class TestMigrator extends Migrator
{
    /**
     * @inheritDoc
     */
    protected function dropTables(string $connection, array $skip = []): void
    {
        parent::dropTables($connection, $skip);

        /** @var \Cake\Database\Connection $db */
        $db = ConnectionManager::get($connection);
        $functions = $db->execute(
            "SELECT p.oid::regprocedure::text
             FROM pg_proc p
             JOIN pg_namespace n ON n.oid = p.pronamespace
             WHERE n.nspname = 'public'
               AND NOT EXISTS (SELECT 1 FROM pg_depend d WHERE d.objid = p.oid AND d.deptype = 'e')",
        )->fetchAll();
        foreach ($functions as $function) {
            $db->execute('DROP FUNCTION IF EXISTS ' . $function[0] . ' CASCADE');
        }
    }
}
