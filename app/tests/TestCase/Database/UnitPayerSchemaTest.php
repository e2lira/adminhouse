<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Database\RowLevelSecurity;
use App\Test\Support\DatabaseTestCase;

/**
 * Database-level guarantees of the payer role and its history (spec occupancies: Responsible payer, Payer change
 * history, Database enforcement; design AD11, S-2; tasks 13.1 and 13.2). Fixtures and assertions use the owner login:
 * these are table constraints and triggers, not access rules (those are in RlsUnitPayerTest).
 */
class UnitPayerSchemaTest extends DatabaseTestCase
{
    private const NOT_NULL = '23502';

    private const FOREIGN_KEY = '23503';

    private const CHECK = '23514';

    private const RESTRICT = '23001';

    private const DENIED = '42501';

    private int $devA;

    private int $devB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->devA = $this->createDevelopment('A');
        $this->devB = $this->createDevelopment('B');
    }

    private function privilege(string $kind, string $table, ?string $column = null): bool
    {
        $sql = $column === null
            ? 'SELECT has_table_privilege(?, ?, ?)'
            : 'SELECT has_column_privilege(?, ?, ?, ?)';
        $params = $column === null
            ? [RowLevelSecurity::ROLE_APP, $table, $kind]
            : [RowLevelSecurity::ROLE_APP, $table, $column, $kind];

        return (bool)$this->db()->execute($sql, $params)->fetch()[0];
    }

    /**
     * @param int $unitId Unit.
     * @return list<array{string, string|null}> `payer`, `changed_by_user_id` of every history row of the unit.
     */
    private function history(int $unitId): array
    {
        return array_map(
            static fn(array $row): array => [$row[0], $row[1] === null ? null : (string)$row[1]],
            $this->db()->execute(
                'SELECT payer, changed_by_user_id FROM unit_payer_changes WHERE unit_id = ? ORDER BY id',
                [$unitId],
            )->fetchAll(),
        );
    }

    public function testThePayerIsARoleThatDefaultsToOwnerAndNeverNull(): void
    {
        $column = $this->db()->execute(
            "SELECT data_type, is_nullable, column_default FROM information_schema.columns
             WHERE table_name = 'units' AND column_name = 'payer'",
        )->fetch();
        $this->assertSame(['text', 'NO', "'owner'::text"], $column);

        $unit = $this->seedUnit($this->devA, null, '101');
        $this->assertSame('owner', $this->db()->execute('SELECT payer FROM units WHERE id = ?', [$unit])->fetch()[0]);

        $this->assertNull($this->ownerSqlState("UPDATE units SET payer = 'tenant' WHERE id = ?", [$unit]));
        $this->assertNull($this->ownerSqlState("UPDATE units SET payer = 'owner' WHERE id = ?", [$unit]));
        $this->assertSame(self::CHECK, $this->ownerSqlState("UPDATE units SET payer = 'landlord' WHERE id = ?", [$unit]));
        $this->assertSame(self::CHECK, $this->ownerSqlState("UPDATE units SET payer = '' WHERE id = ?", [$unit]));
        $this->assertSame(self::CHECK, $this->ownerSqlState("UPDATE units SET payer = 'Owner' WHERE id = ?", [$unit]));
        $this->assertSame(self::NOT_NULL, $this->ownerSqlState('UPDATE units SET payer = NULL WHERE id = ?', [$unit]));
    }

    public function testTheRuntimeRoleCanReadButNeverInsertOrUpdateThePayer(): void
    {
        $this->assertTrue($this->privilege('SELECT', 'units', 'payer'));
        $this->assertFalse($this->privilege('INSERT', 'units', 'payer'));
        $this->assertFalse($this->privilege('UPDATE', 'units', 'payer'));
        $this->assertFalse($this->privilege('REFERENCES', 'units', 'payer'));

        // The grants of the other columns stay as the units migration left them.
        foreach (['section_id', 'number', 'type', 'lot_area_m2', 'indiviso_pct', 'is_active', 'modified'] as $column) {
            $this->assertTrue($this->privilege('UPDATE', 'units', $column), 'UPDATE ' . $column);
        }
        foreach (['id', 'development_id', 'created', 'payer'] as $column) {
            $this->assertFalse($this->privilege('UPDATE', 'units', $column), 'UPDATE ' . $column);
        }
        $this->assertFalse($this->privilege('UPDATE', 'units'));
        $this->assertFalse($this->privilege('INSERT', 'units'));
    }

    public function testTheHistoryHasExactlyTheDocumentedColumns(): void
    {
        $columns = array_map(
            static fn(array $row): string => implode(':', $row),
            $this->db()->execute(
                "SELECT column_name, data_type, is_nullable FROM information_schema.columns
                 WHERE table_name = 'unit_payer_changes' ORDER BY ordinal_position",
            )->fetchAll(),
        );

        $this->assertSame([
            'id:bigint:NO',
            'development_id:bigint:NO',
            'unit_id:bigint:NO',
            'payer:text:NO',
            'changed_at:timestamp without time zone:NO',
            'changed_by_user_id:bigint:YES',
        ], $columns);
    }

    public function testTheHistoryRejectsAnInvalidPayerAndAMismatchedUnit(): void
    {
        $unitA = $this->seedUnit($this->devA, null, '101');
        $this->seedUnit($this->devB, null, '201');
        $insert = 'INSERT INTO unit_payer_changes (development_id, unit_id, payer, changed_at) VALUES (?, ?, ?, NOW())';

        $this->assertNull($this->ownerSqlState($insert, [$this->devA, $unitA, 'tenant']));
        $this->assertSame(self::CHECK, $this->ownerSqlState($insert, [$this->devA, $unitA, 'landlord']));
        // The unit must belong to the development of the row: a composite key, never a bare unit id.
        $this->assertSame(self::FOREIGN_KEY, $this->ownerSqlState($insert, [$this->devB, $unitA, 'owner']));
        $this->assertSame(self::FOREIGN_KEY, $this->ownerSqlState($insert, [$this->devA, $unitA + 1000, 'owner']));
        $this->assertSame(
            self::NOT_NULL,
            $this->ownerSqlState('INSERT INTO unit_payer_changes (development_id, unit_id, payer) VALUES (?, ?, ?)', [$this->devA, $unitA, 'owner']),
        );
    }

    public function testAUnitWithHistoryCannotBeDeleted(): void
    {
        $unit = $this->seedUnit($this->devA, null, '101');

        $this->assertSame(self::RESTRICT, $this->ownerSqlState('DELETE FROM units WHERE id = ?', [$unit]));
        $this->assertSame(1, (int)$this->db()->execute('SELECT COUNT(*) FROM units WHERE id = ?', [$unit])->fetch()[0]);
        $this->assertCount(1, $this->history($unit));
    }

    public function testNoRoleCanUpdateOrDeleteAHistoryRow(): void
    {
        $unit = $this->seedUnit($this->devA, null, '101');

        // The owner login (BYPASSRLS, owns the table) is stopped by the trigger, not by privileges.
        $this->assertSame(self::DENIED, $this->ownerSqlState("UPDATE unit_payer_changes SET payer = 'tenant' WHERE unit_id = ?", [$unit]));
        $this->assertSame(self::DENIED, $this->ownerSqlState('UPDATE unit_payer_changes SET changed_by_user_id = NULL'));
        $this->assertSame(self::DENIED, $this->ownerSqlState('DELETE FROM unit_payer_changes WHERE unit_id = ?', [$unit]));
        $this->assertSame(self::DENIED, $this->ownerSqlState('DELETE FROM unit_payer_changes'));
        // An UPDATE that changes nothing is still an UPDATE.
        $this->assertSame(self::DENIED, $this->ownerSqlState('UPDATE unit_payer_changes SET payer = payer'));
        $this->assertSame([['owner', null]], $this->history($unit));

        // The runtime role has no privilege to try: SELECT only.
        $this->assertTrue($this->privilege('SELECT', 'unit_payer_changes'));
        foreach (['INSERT', 'UPDATE', 'DELETE', 'TRUNCATE', 'REFERENCES', 'TRIGGER'] as $kind) {
            $this->assertFalse($this->privilege($kind, 'unit_payer_changes'), $kind);
        }
        foreach (['payer', 'changed_by_user_id', 'changed_at'] as $column) {
            $this->assertFalse($this->privilege('UPDATE', 'unit_payer_changes', $column), $column);
            $this->assertFalse($this->privilege('INSERT', 'unit_payer_changes', $column), $column);
        }
        $this->assertFalse((bool)$this->db()->execute(
            'SELECT has_table_privilege(?, ?, ?)',
            ['public', 'unit_payer_changes', 'SELECT'],
        )->fetch()[0]);
    }

    public function testAnInsertedUnitStartsItsHistoryAndEveryChangeAppendsARow(): void
    {
        $unit = $this->seedUnit($this->devA, null, '101');
        $this->assertSame([['owner', null]], $this->history($unit));

        $row = $this->db()->execute(
            'SELECT development_id, changed_at >= NOW() - INTERVAL \'1 minute\', changed_at <= clock_timestamp()
             FROM unit_payer_changes WHERE unit_id = ?',
            [$unit],
        )->fetch();
        $this->assertSame($this->devA, (int)$row[0]);
        $this->assertTrue((bool)$row[1] && (bool)$row[2], 'changed_at comes from the database clock');

        $this->db()->execute("UPDATE units SET payer = 'tenant' WHERE id = ?", [$unit]);
        $this->db()->execute("UPDATE units SET payer = 'owner' WHERE id = ?", [$unit]);
        $this->assertSame([['owner', null], ['tenant', null], ['owner', null]], $this->history($unit));

        // Not a change: the same value, or another column.
        $this->db()->execute("UPDATE units SET payer = 'owner' WHERE id = ?", [$unit]);
        $this->db()->execute("UPDATE units SET number = '101-B', modified = NOW() WHERE id = ?", [$unit]);
        $this->assertCount(3, $this->history($unit));

        // Another unit is untouched.
        $other = $this->seedUnit($this->devB, null, '201');
        $this->assertSame([['owner', null]], $this->history($other));
        $this->assertCount(3, $this->history($unit));
    }

    public function testTheHistoryOfAUnitCarriesItsDevelopment(): void
    {
        $unit = $this->seedUnit($this->devB, null, '201');

        $this->assertSame(
            $this->devB,
            (int)$this->db()->execute('SELECT development_id FROM unit_payer_changes WHERE unit_id = ?', [$unit])->fetch()[0],
        );
    }

    public function testTheTriggerFunctionsArePinnedOwnedByTheDefinerAndNotCallable(): void
    {
        $rows = $this->db()->execute(
            "SELECT proname, pg_get_userbyid(proowner), prosecdef, proconfig::text,
                    has_function_privilege('kualitet_app', oid, 'EXECUTE'), has_function_privilege('public', oid, 'EXECUTE')
             FROM pg_proc
             WHERE proname IN ('unit_payer_changes_reject_change', 'unit_payer_changes_log') AND pronamespace = 'public'::regnamespace
             ORDER BY proname",
        )->fetchAll();

        $this->assertCount(2, $rows);
        $securityDefiner = [];
        foreach ($rows as $row) {
            $securityDefiner[$row[0]] = (bool)$row[2];
            $this->assertSame('kualitet_definer', $row[1], $row[0]);
            $this->assertSame('{"search_path=pg_catalog, pg_temp"}', $row[3], $row[0]);
            $this->assertFalse((bool)$row[4], $row[0] . ' is not callable by the runtime role');
            $this->assertFalse((bool)$row[5], $row[0] . ' is not callable by PUBLIC');
        }
        // The logging function must run with the rights of the definer: the runtime role cannot insert history.
        $this->assertSame(
            ['unit_payer_changes_log' => true, 'unit_payer_changes_reject_change' => false],
            $securityDefiner,
        );
    }

    public function testTheTriggersAreTheExpectedOnes(): void
    {
        $names = fn(string $table): array => array_map(
            static fn(array $row): string => $row[0],
            $this->db()->execute(
                'SELECT tgname FROM pg_trigger WHERE tgrelid = to_regclass(?) AND NOT tgisinternal ORDER BY tgname',
                [$table],
            )->fetchAll(),
        );

        $this->assertSame(['unit_payer_changes_append_only'], $names('public.unit_payer_changes'));
        $this->assertSame(
            ['units_forbid_development_change', 'units_log_payer_insert', 'units_log_payer_update'],
            $names('public.units'),
        );
    }
}
