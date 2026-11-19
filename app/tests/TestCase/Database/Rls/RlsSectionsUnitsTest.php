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
     * @return array<string, array{string}>
     */
    public static function tables(): array
    {
        return [
            'sections' => ['sections'],
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
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

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
        foreach (['adminA', 'accountantA', 'dataEntryA'] as $user) {
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
            $this->assertRejected($this->insertSql($table), [$this->devB, 'new_' . $user]);
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

            $this->assertRejected($this->insertSql($table), [$this->devA, 'new']);
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
        $this->assertRejected($this->insertSql($table), [$this->devA, 'new']);
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
        $this->assertRejected($this->insertSql($table), [$this->devA, 'new']);
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
    public function testNoRoleMovesARowOrRewritesItsKeyOrCreationTime(string $table): void
    {
        $column = $this->labelColumn($table);
        foreach (['adminA', 'superUser'] as $user) {
            $this->actAs($this->{$user});

            $this->assertRejected("UPDATE {$table} SET development_id = ? WHERE {$column} = 'a1'", [$this->devB]);
            $this->assertRejected("UPDATE {$table} SET id = id + 100 WHERE {$column} = 'a1'");
            $this->assertRejected("UPDATE {$table} SET created = NOW() WHERE {$column} = 'a1'");
            $this->assertRejected(
                "INSERT INTO {$table} (id, development_id, {$column}, created, modified) VALUES (999, ?, 'x', NOW(), NOW())",
                [$this->devA],
            );
            $this->assertRowsUntouched($table);
        }
    }
}
