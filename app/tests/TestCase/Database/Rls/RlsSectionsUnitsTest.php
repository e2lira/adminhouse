<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use App\Database\RowLevelSecurity;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Row-level security of the managed domain tables `sections` and `units` (spec units: Database enforcement; design
 * AD7 and amendment A1): reads follow `units.view`, INSERT and UPDATE follow `units.manage`, DELETE is for the
 * super-administrator only. One scenario runs against both tables, keyed by their label column (`name` for sections,
 * `number` for units).
 *
 * Every denial is checked twice: the statement is refused or matches no row, and the owner connection then shows the
 * rows unchanged (an RLS-blocked UPDATE or DELETE matches zero rows rather than raising an error).
 */
class RlsSectionsUnitsTest extends RlsTestCase
{
    /**
     * SQLSTATE of a policy violation or a missing privilege (insufficient_privilege).
     */
    private const DENIED = '42501';

    /**
     * Holds `units.view` but not `units.manage`; created here because only this test needs it.
     */
    private int $supervisorA;

    /**
     * @return array<string, array{string}>
     */
    public static function tables(): array
    {
        return [
            'sections' => ['sections'],
            'units' => ['units'],
        ];
    }

    private function labelColumn(string $table): string
    {
        return $table === 'sections' ? 'name' : 'number';
    }

    /**
     * Insert a row with the owner connection or the runtime role; the statement is the same for both.
     *
     * @param string $table Table.
     * @return string
     */
    private function insertSql(string $table): string
    {
        return match ($table) {
            'sections' => 'INSERT INTO sections (development_id, name, created, modified) VALUES (?, ?, NOW(), NOW())',
            'units' => "INSERT INTO units (development_id, number, type, created, modified)
                        VALUES (?, ?, 'house', NOW(), NOW())",
        };
    }

    private function insertWithIdSql(string $table): string
    {
        return match ($table) {
            'sections' => 'INSERT INTO sections (id, development_id, name, created, modified)
                           VALUES (999, ?, ?, NOW(), NOW())',
            'units' => "INSERT INTO units (id, development_id, number, type, created, modified)
                        VALUES (999, ?, ?, 'house', NOW(), NOW())",
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisorA = $this->createUser('supervisor_a', null);
        $this->assignRole($this->supervisorA, 'supervisor', $this->devA);
        foreach ($this->tables() as [$table]) {
            foreach ([[$this->devA, 'a1'], [$this->devA, 'a2'], [$this->devB, 'b1']] as $params) {
                $this->db()->execute($this->insertSql($table), $params);
            }
        }
    }

    /**
     * @param string $table Table.
     * @return list<string> Labels the runtime role reads.
     */
    private function labels(string $table): array
    {
        $column = $this->labelColumn($table);
        $rows = $this->appDb()->execute("SELECT {$column} FROM {$table} ORDER BY {$column}")->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    /**
     * Commit the session and read every row back as the owner: nothing was inserted, changed or deleted.
     *
     * @param string $table Table.
     * @return void
     */
    private function assertRowsUntouched(string $table): void
    {
        $this->commitSession();
        $column = $this->labelColumn($table);
        $rows = $this->db()->execute(
            "SELECT {$column}, development_id FROM {$table} ORDER BY {$column}",
        )->fetchAll();

        $this->assertSame(
            [['a1', $this->devA], ['a2', $this->devA], ['b1', $this->devB]],
            array_map(static fn(array $row): array => [$row[0], (int)$row[1]], $rows),
        );
    }

    private function update(string $table, string $from, string $to): string
    {
        $column = $this->labelColumn($table);

        return "UPDATE {$table} SET {$column} = '{$to}', modified = NOW() WHERE {$column} = '{$from}'";
    }

    public function testTheTablesAreForcedAndGrantedLikeAManagedDomainTable(): void
    {
        foreach ($this->tables() as [$table]) {
            $flags = $this->db()->execute(
                'SELECT relrowsecurity, relforcerowsecurity FROM pg_class WHERE oid = ?::regclass',
                [$table],
            )->fetch();
            $this->assertTrue((bool)$flags[0], $table . ' enables RLS');
            $this->assertTrue((bool)$flags[1], $table . ' forces RLS');

            $policies = array_map(
                static fn(array $row): string => $row[0] . ':' . $row[1],
                $this->db()->execute(
                    'SELECT policyname, cmd FROM pg_policies WHERE tablename = ? ORDER BY policyname',
                    [$table],
                )->fetchAll(),
            );
            $this->assertSame(
                [$table . '_delete:DELETE', $table . '_insert:INSERT', $table . '_select:SELECT', $table . '_update:UPDATE'],
                $policies,
            );

            $privilege = fn(string $kind, string $column): bool => (bool)$this->db()->execute(
                'SELECT has_column_privilege(?, ?, ?, ?)',
                [RowLevelSecurity::ROLE_APP, $table, $column, $kind],
            )->fetch()[0];
            $this->assertFalse($privilege('UPDATE', 'id'), $table . ' UPDATE id');
            $this->assertFalse($privilege('UPDATE', 'development_id'), $table . ' UPDATE development_id');
            $this->assertFalse($privilege('UPDATE', 'created'), $table . ' UPDATE created');
            $this->assertTrue($privilege('UPDATE', 'modified'), $table . ' UPDATE modified');
            $this->assertTrue($privilege('INSERT', 'development_id'), $table . ' INSERT development_id');
            $this->assertTrue($privilege('INSERT', 'created'), $table . ' INSERT created');
            $this->assertFalse($privilege('INSERT', 'id'), $table . ' INSERT id');
        }
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testReadsFollowTheUnitsViewPermission(string $table): void
    {
        foreach (['adminA', 'accountantA', 'dataEntryA', 'supervisorA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame(['a1', 'a2'], $this->labels($table), $user);
            $this->endSession();
        }
        $this->actAs($this->adminB);
        $this->assertSame(['b1'], $this->labels($table));
        $this->endSession();

        // They hold an assignment in A, but their role lacks units.view (the resident lost it in AD13).
        foreach (['resident', 'guardA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->devA], $this->ids('SELECT unnest(dev_ids())'), $user);
            $this->assertSame([], $this->labels($table), $user);
            $this->endSession();
        }
        foreach (['disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([], $this->labels($table), $user);
            $this->endSession();
        }
        $this->actAs($this->superUser);
        $this->assertSame(['a1', 'a2', 'b1'], $this->labels($table));
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testAJobAndASessionlessRequestReadNothing(string $table): void
    {
        $this->actAsJob([$this->devA, $this->devB]);
        $this->assertSame([$this->devA, $this->devB], $this->ids('SELECT unnest(dev_ids())'));
        $this->assertSame([], $this->labels($table));
        $this->endSession();

        $this->actAs(null);
        $this->assertSame([], $this->labels($table));
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testAdministratorAndDataEntryWriteInTheirDevelopmentOnly(string $table): void
    {
        $column = $this->labelColumn($table);
        foreach (['adminA', 'dataEntryA'] as $user) {
            $this->actAs($this->{$user});

            $this->assertAllowed($this->insertSql($table), [$this->devA, 'new_' . $user]);
            $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql($table), [$this->devB, 'new_' . $user]));
            $this->assertAllowed($this->update($table, 'a1', 'a1'));
            $this->assertAllowed($this->update($table, 'a2', 'a2'));
            // B is invisible to them: the update matches nothing.
            $this->assertSame(0, $this->attempt($this->update($table, 'b1', 'hijacked')));
            $this->endSession();
        }

        $rows = $this->db()->execute("SELECT {$column} FROM {$table} WHERE development_id = ? ORDER BY {$column}", [$this->devB])->fetchAll();
        $this->assertSame([['b1']], $rows);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonWriters(): array
    {
        return [
            'accountant (reads, role lacks units.manage)' => ['accountantA'],
            'supervisor (reads, role lacks units.manage)' => ['supervisorA'],
            'guard' => ['guardA'],
            'resident' => ['resident'],
            'administrator of the other development' => ['adminB'],
            'disabled administrator' => ['disabledAdmin'],
            'ended assignment' => ['endedAdmin'],
            'future assignment' => ['futureAdmin'],
            'user without assignments' => ['loner'],
        ];
    }

    /**
     * @param string $user Property holding the user id.
     * @return void
     */
    #[DataProvider('nonWriters')]
    public function testWhoLacksTheWritePermissionInADevelopmentCannotWriteThere(string $user): void
    {
        foreach ($this->tables() as [$table]) {
            $this->actAs($this->{$user});

            $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql($table), [$this->devA, 'new']));
            $this->assertSame(0, $this->attempt($this->update($table, 'a1', 'x')));
            $this->assertSame(0, $this->attempt("DELETE FROM {$table} WHERE development_id = {$this->devA}"));
            $this->assertRowsUntouched($table);
        }
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testAReadOnlyAssignmentReadsButDoesNotWrite(string $table): void
    {
        $user = $this->createUser('observer', null);
        $this->assignRole($user, 'administrator', $this->devA, -30, null, true);
        $this->actAs($user);

        $this->assertSame(['a1', 'a2'], $this->labels($table));
        $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql($table), [$this->devA, 'new']));
        $this->assertSame(0, $this->attempt($this->update($table, 'a1', 'x')));
        $this->assertSame(0, $this->attempt("DELETE FROM {$table}"));
        $this->assertRowsUntouched($table);
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testAnArchivedDevelopmentCanBeReadButNotWrittenByItsStaff(string $table): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
        $this->actAs($this->adminA);

        $this->assertSame(['a1', 'a2'], $this->labels($table));
        $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql($table), [$this->devA, 'new']));
        $this->assertSame(0, $this->attempt($this->update($table, 'a1', 'x')));
        $this->assertRowsUntouched($table);
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testTheSuperAdministratorWritesAnywhere(string $table): void
    {
        $this->actAs($this->superUser);

        $this->assertAllowed($this->insertSql($table), [$this->devB, 'new']);
        $this->assertAllowed($this->update($table, 'b1', 'b1'));
        $this->assertAllowed($this->update($table, 'a1', 'a1'));
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testOnlyTheSuperAdministratorDeletes(string $table): void
    {
        foreach (['adminA', 'dataEntryA', 'accountantA', 'adminB'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame(0, $this->attempt("DELETE FROM {$table}"), $user);
            $this->assertRowsUntouched($table);
        }
        $this->actAsJob([$this->devA, $this->devB]);
        $this->assertSame(0, $this->attempt("DELETE FROM {$table}"));
        $this->assertRowsUntouched($table);

        $this->actAs($this->superUser);
        if ($table === 'units') {
            // The policy lets the super-administrator through (a blocked DELETE matches no row, it does not raise);
            // what refuses is the payer history every unit has (PR 5b, ON DELETE RESTRICT: 23001).
            $this->assertSame('23001', $this->sqlStateOf("DELETE FROM units WHERE development_id = {$this->devA}"));
            $this->assertRowsUntouched($table);

            return;
        }
        $this->assertAllowed("DELETE FROM {$table} WHERE development_id = {$this->devA}");
        $this->commitSession();
        $column = $this->labelColumn($table);
        $remaining = $this->db()->execute("SELECT {$column} FROM {$table}")->fetchAll();
        $this->assertSame([['b1']], $remaining);
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testAnExistingLabelInAnotherDevelopmentDoesNotCollideAndSoCannotBeProbed(string $table): void
    {
        $this->actAs($this->adminA);

        // 'b1' exists only in development B, which adminA cannot read: the unique key is scoped per development, so
        // this succeeds instead of answering "duplicate" (which would reveal that B holds it).
        $this->assertAllowed($this->insertSql($table), [$this->devA, 'b1']);
        // A real duplicate inside their own development is still refused.
        $this->assertSame('23505', $this->sqlStateOf($this->insertSql($table), [$this->devA, 'a1']));
    }

    /**
     * @return array<string, array{string, list<string>, list<string>}>
     */
    public static function writableColumns(): array
    {
        return [
            'sections' => ['sections', ['name', 'modified'], ['development_id', 'created']],
            'units' => [
                'units',
                ['section_id', 'number', 'type', 'lot_area_m2', 'indiviso_pct', 'is_active', 'modified'],
                ['development_id', 'created'],
            ],
        ];
    }

    /**
     * @param string $table Table.
     * @param list<string> $updatable Columns the runtime role may update (and insert).
     * @param list<string> $insertOnly Columns it may only set on insert (besides the updatable ones).
     * @return void
     */
    #[DataProvider('writableColumns')]
    public function testTheRuntimeRoleHoldsExactlyThePrivilegesOnEveryColumn(
        string $table,
        array $updatable,
        array $insertOnly,
    ): void {
        $all = array_map(
            static fn(array $row): string => $row[0],
            $this->db()->execute(
                'SELECT column_name FROM information_schema.columns WHERE table_name = ? ORDER BY ordinal_position',
                [$table],
            )->fetchAll(),
        );
        $privilege = fn(string $kind, string $column): bool => (bool)$this->db()->execute(
            'SELECT has_column_privilege(?, ?, ?, ?)',
            [RowLevelSecurity::ROLE_APP, $table, $column, $kind],
        )->fetch()[0];

        foreach ($all as $column) {
            $this->assertSame(in_array($column, $updatable, true), $privilege('UPDATE', $column), 'UPDATE ' . $column);
            $this->assertSame(
                in_array($column, [...$updatable, ...$insertOnly], true),
                $privilege('INSERT', $column),
                'INSERT ' . $column,
            );
        }
        $this->assertContains('modified', $updatable);
        $this->assertSame([], array_diff([...$updatable, ...$insertOnly], $all), 'every granted column exists');
    }

    public function testStaffUpdateEveryUpdatableColumnOfAUnitAndASection(): void
    {
        foreach (['adminA', 'dataEntryA'] as $user) {
            $this->actAs($this->{$user});

            $this->assertSame(1, $this->attempt(
                "UPDATE units SET section_id = NULL, number = 'a1', type = 'lot', lot_area_m2 = 10.5,
                    indiviso_pct = 1.5, is_active = false, modified = NOW() WHERE number = 'a1'",
            ), $user);
            $this->assertSame(1, $this->attempt("UPDATE sections SET name = 'a1', modified = NOW() WHERE name = 'a1'"), $user);
            $this->commitSession();

            $row = $this->db()->execute(
                "SELECT type, lot_area_m2::text, indiviso_pct::text, is_active FROM units WHERE number = 'a1'",
            )->fetch();
            $this->assertSame(['lot', '10.50', '1.5000', false], [$row[0], $row[1], $row[2], (bool)$row[3]], $user);
            $this->db()->execute("UPDATE units SET type = 'house', lot_area_m2 = NULL, indiviso_pct = NULL, is_active = true");
        }
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testAJobAndAnAnonymousRequestCannotInsertUpdateOrDelete(string $table): void
    {
        $sessions = [
            'job' => fn() => $this->actAsJob([$this->devA, $this->devB]),
            'anonymous' => fn() => $this->actAs(null),
        ];
        foreach ($sessions as $name => $start) {
            $start();

            foreach ([$this->devA, $this->devB] as $development) {
                $this->assertSame(
                    self::DENIED,
                    $this->sqlStateOf($this->insertSql($table), [$development, 'new']),
                    $name,
                );
            }
            $this->assertSame(0, $this->attempt($this->update($table, 'a1', 'x')), $name);
            $this->assertSame(0, $this->attempt($this->update($table, 'b1', 'x')), $name);
            $this->assertSame(0, $this->attempt("DELETE FROM {$table}"), $name);
            $this->assertRowsUntouched($table);
        }
    }

    public function testAUnitCannotPointAtASectionOfAnotherDevelopmentAndTheAnswerHidesWhetherItExists(): void
    {
        $own = (int)$this->db()->execute(
            "INSERT INTO sections (development_id, name, created, modified)
             VALUES (?, 'own', NOW(), NOW()) RETURNING id",
            [$this->devA],
        )->fetch()[0];
        $foreign = (int)$this->db()->execute("SELECT id FROM sections WHERE name = 'b1'")->fetch()[0];
        $insert = "INSERT INTO units (development_id, section_id, number, type, created, modified)
                   VALUES (?, ?, ?, 'house', NOW(), NOW())";
        $this->actAs($this->dataEntryA);

        $this->assertAllowed($insert, [$this->devA, $own, 'in_own_section']);
        // The section of B is invisible to this user, and the answer for it equals the answer for a missing id.
        $this->assertSame('23503', $this->sqlStateOf($insert, [$this->devA, $foreign, 'x']));
        $this->assertSame('23503', $this->sqlStateOf($insert, [$this->devA, $foreign + 1000, 'x']));
        // Nor can the row be written into B to use B's section.
        $this->assertRejected($insert, [$this->devB, $foreign, 'x']);

        // The same holds when an existing unit is moved to another section.
        $this->assertAllowed("UPDATE units SET section_id = {$own}, modified = NOW() WHERE number = 'a1'");
        $this->assertSame(
            '23503',
            $this->sqlStateOf("UPDATE units SET section_id = {$foreign} WHERE number = 'a2'"),
        );
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('tables')]
    public function testNoRoleMovesARowOrRewritesItsKeyOrCreationTime(string $table): void
    {
        $column = $this->labelColumn($table);
        foreach (['adminA', 'superUser'] as $user) {
            $this->actAs($this->{$user});

            $denied = fn(string $sql, array $params = []) => $this->assertSame(self::DENIED, $this->sqlStateOf($sql, $params));
            $denied("UPDATE {$table} SET development_id = ? WHERE {$column} = 'a1'", [$this->devB]);
            $denied("UPDATE {$table} SET id = id + 100 WHERE {$column} = 'a1'");
            $denied("UPDATE {$table} SET created = NOW() WHERE {$column} = 'a1'");
            // The statement is complete (type included), so only the column privilege on `id` can refuse it.
            $denied($this->insertWithIdSql($table), [$this->devA, 'x']);
            $this->assertRowsUntouched($table);
        }
    }
}
