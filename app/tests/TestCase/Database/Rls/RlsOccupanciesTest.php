<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use App\Database\RowLevelSecurity;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * Row-level security of `occupancies` (spec occupancies: Who may read and write, History is kept, Database
 * enforcement; design AD7, A1, A5; task 12.7). Reads follow `people.view` (A5), INSERT and UPDATE follow
 * `occupancies.manage`, DELETE is for the super-administrator only, and there is no own-row clause (D7).
 *
 * Every denial is checked twice: the statement is refused or matches no row, and the owner connection then shows the
 * rows unchanged (an RLS-blocked UPDATE or DELETE matches zero rows rather than raising an error).
 */
class RlsOccupanciesTest extends RlsTestCase
{
    /**
     * SQLSTATE of a policy violation or a missing privilege (insufficient_privilege).
     */
    private const DENIED = '42501';

    private const CHECK = '23514';

    private const FOREIGN_KEY = '23503';

    private const EXCLUSION = '23P01';

    private int $supervisorA;

    private int $unitA1;

    private int $unitA2;

    private int $unitB;

    private int $personA;

    /**
     * Rows as the owner stored them: `a1` (primary owner, resident's person), `a2` (non-primary tenant of A, shared
     * person), `b1` (primary owner of B).
     *
     * @var array<string, int>
     */
    private array $row = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisorA = $this->createUser('supervisor_a', null);
        $this->assignRole($this->supervisorA, 'supervisor', $this->devA);
        $this->unitA1 = $this->seedUnit($this->devA, null, '101');
        $this->unitA2 = $this->seedUnit($this->devA, null, '102');
        $this->unitB = $this->seedUnit($this->devB, null, '201');
        $this->personA = $this->createPerson('Person A', [$this->devA]);

        $this->row['a1'] = $this->seed($this->devA, $this->unitA1, $this->personResident, 'owner', true);
        $this->row['a2'] = $this->seed($this->devA, $this->unitA2, $this->personShared, 'tenant', false);
        $this->row['b1'] = $this->seed($this->devB, $this->unitB, $this->personB, 'owner', true);
    }

    private function seed(int $developmentId, int $unitId, int $personId, string $role, bool $primary): int
    {
        return (int)$this->db()->execute(
            'INSERT INTO occupancies (development_id, unit_id, person_id, role, is_primary, starts_on, created, modified)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW()) RETURNING id',
            [$developmentId, $unitId, $personId, $role, $primary ? 't' : 'f', '2026-01-01'],
        )->fetch()[0];
    }

    /**
     * An INSERT the runtime role runs for a new occupancy; the same statement serves every actor.
     *
     * @return string
     */
    private function insertSql(): string
    {
        return "INSERT INTO occupancies (development_id, unit_id, person_id, role, is_primary, starts_on, created, modified)
                VALUES (?, ?, ?, 'tenant', false, '2026-03-01', NOW(), NOW()) RETURNING id";
    }

    /**
     * @param int $developmentId Development of the new row.
     * @param int $unitId Unit.
     * @param int $personId Person.
     * @return list<int>
     */
    private function newRow(int $developmentId, int $unitId, int $personId): array
    {
        return [$developmentId, $unitId, $personId];
    }

    /**
     * @return list<int> Ids of the rows the runtime role reads, in the order a1, a2, b1 by `$this->row`.
     */
    private function readable(): array
    {
        return $this->visibleIds('occupancies');
    }

    /**
     * Commit the session and read every occupancy back as the owner: nothing was inserted, changed or deleted.
     *
     * @return void
     */
    private function assertRowsUntouched(): void
    {
        $this->commitSession();
        $rows = $this->db()->execute(
            'SELECT id, development_id, ends_on::text, role, is_primary FROM occupancies ORDER BY id',
        )->fetchAll();

        $this->assertSame([
            [$this->row['a1'], $this->devA, null, 'owner', true],
            [$this->row['a2'], $this->devA, null, 'tenant', false],
            [$this->row['b1'], $this->devB, null, 'owner', true],
        ], array_map(static fn(array $r): array => [(int)$r[0], (int)$r[1], $r[2], $r[3], (bool)$r[4]], $rows));
    }

    /**
     * Run a statement as the runtime role and report SQLSTATE and the constraint it names.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Parameters.
     * @return array{string|null, string|null}
     */
    private function failure(string $sql, array $params = []): array
    {
        $connection = $this->appDb();
        $connection->execute('SAVEPOINT failure');
        try {
            $connection->execute($sql, $params)->fetchAll();
            $connection->execute('RELEASE SAVEPOINT failure');

            return [null, null];
        } catch (Throwable $e) {
            $connection->execute('ROLLBACK TO SAVEPOINT failure');
            for (; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                    $named = preg_match('/occupancies_[a-z_]+/', (string)$e->errorInfo[2], $match) === 1;

                    return [(string)$e->errorInfo[0], $named ? $match[0] : null];
                }
            }

            return ['unknown', null];
        }
    }

    public function testTheTableIsForcedAndHasOneNamedPolicyPerCommand(): void
    {
        $flags = $this->db()->execute(
            "SELECT relrowsecurity, relforcerowsecurity FROM pg_class WHERE oid = 'occupancies'::regclass",
        )->fetch();
        $this->assertTrue((bool)$flags[0]);
        $this->assertTrue((bool)$flags[1]);

        $policies = array_map(
            static fn(array $row): string => $row[0] . ':' . $row[1] . ':' . $row[2],
            $this->db()->execute(
                "SELECT policyname, cmd, roles::text FROM pg_policies WHERE tablename = 'occupancies' ORDER BY policyname",
            )->fetchAll(),
        );
        $this->assertSame([
            'occupancies_delete:DELETE:{kualitet_app}',
            'occupancies_insert:INSERT:{kualitet_app}',
            'occupancies_select:SELECT:{kualitet_app}',
            'occupancies_update:UPDATE:{kualitet_app}',
        ], $policies);
    }

    public function testThePoliciesFollowThePermissionsExactly(): void
    {
        $policy = fn(string $name): array => $this->db()->execute(
            'SELECT qual, with_check FROM pg_policies WHERE tablename = ? AND policyname = ?',
            ['occupancies', $name],
        )->fetch();

        // A5: reads follow people.view, with no own-row clause (D7), so `person_id` never appears.
        $select = $policy('occupancies_select')[0];
        $this->assertStringContainsString("permission_dev_ids('people.view'::text)", $select);
        $this->assertStringContainsString('is_super()', $select);
        $this->assertStringNotContainsString('person_id', $select);
        $this->assertStringNotContainsString('dev_ids() AS', $select);
        $this->assertStringNotContainsString('occupancies.manage', $select);

        foreach (['occupancies_insert' => 1, 'occupancies_update' => 1] as $name => $unused) {
            $this->assertStringContainsString("permission_dev_ids('occupancies.manage'::text)", (string)$policy($name)[1], $name);
            $this->assertStringNotContainsString('people.view', (string)$policy($name)[1], $name);
        }
        $this->assertStringContainsString("permission_dev_ids('occupancies.manage'::text)", $policy('occupancies_update')[0]);
        $this->assertSame('( SELECT is_super() AS is_super)', $policy('occupancies_delete')[0]);
    }

    public function testTheRuntimeRoleHoldsExactlyThePrivilegesOnEveryColumn(): void
    {
        $privilege = fn(string $kind, string $column): bool => (bool)$this->db()->execute(
            'SELECT has_column_privilege(?, ?, ?, ?)',
            [RowLevelSecurity::ROLE_APP, 'occupancies', $column, $kind],
        )->fetch()[0];
        $all = array_map(
            static fn(array $row): string => $row[0],
            $this->db()->execute(
                "SELECT column_name FROM information_schema.columns WHERE table_name = 'occupancies'
                 ORDER BY ordinal_position",
            )->fetchAll(),
        );
        $updatable = ['ends_on', 'modified'];
        $insertable = ['development_id', 'unit_id', 'person_id', 'role', 'is_primary', 'starts_on', 'ends_on', 'created', 'modified'];

        $this->assertCount(10, $all);
        foreach ($all as $column) {
            $this->assertSame(in_array($column, $updatable, true), $privilege('UPDATE', $column), 'UPDATE ' . $column);
            $this->assertSame(in_array($column, $insertable, true), $privilege('INSERT', $column), 'INSERT ' . $column);
        }
        // No table-wide UPDATE or INSERT, no TRUNCATE, no sequence.
        foreach (['UPDATE', 'INSERT', 'TRUNCATE', 'REFERENCES', 'TRIGGER'] as $kind) {
            $this->assertFalse((bool)$this->db()->execute(
                'SELECT has_table_privilege(?, ?, ?)',
                [RowLevelSecurity::ROLE_APP, 'occupancies', $kind],
            )->fetch()[0], $kind);
        }
        $this->assertTrue((bool)$this->db()->execute(
            'SELECT has_table_privilege(?, ?, ?)',
            [RowLevelSecurity::ROLE_APP, 'occupancies', 'SELECT'],
        )->fetch()[0]);
        $this->assertFalse((bool)$this->db()->execute(
            'SELECT has_table_privilege(?, ?, ?)',
            ['public', 'occupancies', 'SELECT'],
        )->fetch()[0]);
    }

    public function testTheGuardTriggerFunctionIsPinnedAndNotCallable(): void
    {
        $row = $this->db()->execute(
            "SELECT pg_get_userbyid(proowner), proconfig::text, prosecdef,
                    has_function_privilege('kualitet_app', oid, 'EXECUTE'),
                    has_function_privilege('public', oid, 'EXECUTE')
             FROM pg_proc WHERE proname = 'occupancies_guard_update' AND pronamespace = 'public'::regnamespace",
        )->fetch();

        $this->assertSame('kualitet_definer', $row[0]);
        $this->assertSame('{"search_path=pg_catalog, pg_temp"}', $row[1]);
        $this->assertFalse((bool)$row[2], 'a trigger function runs as the invoker');
        $this->assertFalse((bool)$row[3], 'the runtime role cannot call it');
        $this->assertFalse((bool)$row[4], 'PUBLIC cannot call it');

        $triggers = array_map(
            static fn(array $r): string => $r[0],
            $this->db()->execute(
                "SELECT tgname FROM pg_trigger WHERE tgrelid = 'occupancies'::regclass AND NOT tgisinternal ORDER BY tgname",
            )->fetchAll(),
        );
        $this->assertSame(['occupancies_forbid_development_change', 'occupancies_guard_update'], $triggers);
    }

    public function testReadsFollowThePeopleViewPermission(): void
    {
        foreach (['adminA', 'accountantA', 'dataEntryA', 'supervisorA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->row['a1'], $this->row['a2']], $this->readable(), $user);
            $this->endSession();
        }
        $this->actAs($this->adminB);
        $this->assertSame([$this->row['b1']], $this->readable());
        $this->endSession();

        // They hold an assignment in A, but their role lacks people.view: the resident lives in `a1` and still
        // reads nothing, because there is no own-row clause (D7).
        foreach (['resident', 'guardA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->devA], $this->ids('SELECT unnest(dev_ids())'), $user);
            $this->assertSame([], $this->readable(), $user);
            $this->endSession();
        }
        foreach (['disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([], $this->readable(), $user);
            $this->endSession();
        }
        $this->actAs($this->superUser);
        $this->assertSame([$this->row['a1'], $this->row['a2'], $this->row['b1']], $this->readable());
    }

    public function testAReadOnlyAssignmentReadsButDoesNotWrite(): void
    {
        $user = $this->createUser('observer', null);
        $this->assignRole($user, 'administrator', $this->devA, -30, null, true);
        $this->actAs($user);

        $this->assertSame([$this->row['a1'], $this->row['a2']], $this->readable());
        $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql(), $this->newRow($this->devA, $this->unitA1, $this->personA)));
        $this->assertSame(0, $this->attempt("UPDATE occupancies SET ends_on = '2026-09-01' WHERE id = {$this->row['a1']}"));
        $this->assertSame(0, $this->attempt('DELETE FROM occupancies'));
        $this->assertRowsUntouched();
    }

    public function testAJobAndAnAnonymousRequestReadAndWriteNothing(): void
    {
        $sessions = [
            'job' => fn() => $this->actAsJob([$this->devA, $this->devB]),
            'anonymous' => fn() => $this->actAs(null),
        ];
        foreach ($sessions as $name => $start) {
            $start();

            $this->assertSame([], $this->readable(), $name);
            foreach ([[$this->devA, $this->unitA1], [$this->devB, $this->unitB]] as [$development, $unit]) {
                $this->assertSame(
                    self::DENIED,
                    $this->sqlStateOf($this->insertSql(), $this->newRow($development, $unit, $this->personShared)),
                    $name,
                );
            }
            $this->assertSame(0, $this->attempt("UPDATE occupancies SET ends_on = '2026-09-01'"), $name);
            $this->assertSame(0, $this->attempt('DELETE FROM occupancies'), $name);
            $this->assertRowsUntouched();
        }
    }

    public function testAdministratorAndDataEntryCreateInTheirDevelopmentOnly(): void
    {
        foreach (['adminA', 'dataEntryA'] as $user) {
            $this->actAs($this->{$user});

            $this->assertAllowed($this->insertSql(), $this->newRow($this->devA, $this->unitA1, $this->personA));
            $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql(), $this->newRow($this->devB, $this->unitB, $this->personB)), $user);
            $this->endSession();
        }

        $count = (int)$this->db()->execute('SELECT COUNT(*) FROM occupancies WHERE development_id = ?', [$this->devB])->fetch()[0];
        $this->assertSame(1, $count);
    }

    public function testAdministratorAndDataEntryEndAnOccupancyOfTheirDevelopmentOnce(): void
    {
        foreach (['adminA' => 'a1', 'dataEntryA' => 'a2'] as $user => $key) {
            $this->actAs($this->{$user});

            $this->assertAllowed("UPDATE occupancies SET ends_on = '2026-09-01', modified = NOW() WHERE id = {$this->row[$key]}");
            // B is invisible to them: the update matches nothing.
            $this->assertSame(0, $this->attempt("UPDATE occupancies SET ends_on = '2026-09-01' WHERE id = {$this->row['b1']}"));
            // The end is set once: the trigger refuses a second change, even a harmless looking one.
            $this->assertSame(self::CHECK, $this->sqlStateOf("UPDATE occupancies SET ends_on = '2026-10-01' WHERE id = {$this->row[$key]}"), $user);
            $this->assertSame(self::CHECK, $this->sqlStateOf("UPDATE occupancies SET ends_on = NULL WHERE id = {$this->row[$key]}"), $user);
            $this->commitSession();
        }

        $rows = $this->db()->execute('SELECT id, ends_on::text FROM occupancies ORDER BY id')->fetchAll();
        $this->assertSame([
            [$this->row['a1'], '2026-09-01'],
            [$this->row['a2'], '2026-09-01'],
            [$this->row['b1'], null],
        ], array_map(static fn(array $r): array => [(int)$r[0], $r[1]], $rows));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function otherEdits(): array
    {
        return [
            'role' => ["role = 'tenant'"],
            'primary flag' => ['is_primary = NOT is_primary'],
            'person' => ['person_id = {person}'],
            'unit' => ['unit_id = {unit}'],
            'start' => ["starts_on = '2026-01-02'"],
            'development' => ['development_id = {devB}'],
            'id' => ['id = id + 1000'],
            'creation time' => ["created = NOW() + interval '1 day'"],
        ];
    }

    /**
     * @param string $assignment SET clause; `{person}`, `{unit}` and `{devB}` stand for fixture ids.
     * @return void
     */
    #[DataProvider('otherEdits')]
    public function testNoRoleEditsAnythingButTheEndAndTheModificationTime(string $assignment): void
    {
        $set = strtr($assignment, [
            '{person}' => $this->personA,
            '{unit}' => $this->unitA2,
            '{devB}' => $this->devB,
        ]);
        foreach (['adminA', 'dataEntryA', 'superUser'] as $user) {
            $this->actAs($this->{$user});

            $this->assertSame(
                self::DENIED,
                $this->sqlStateOf("UPDATE occupancies SET {$set} WHERE id = {$this->row['a1']}"),
                $user,
            );
            $this->assertRowsUntouched();
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonWriters(): array
    {
        return [
            'accountant (reads, role lacks occupancies.manage)' => ['accountantA'],
            'supervisor (reads, role lacks occupancies.manage)' => ['supervisorA'],
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
        $this->actAs($this->{$user});

        $this->assertSame(
            self::DENIED,
            $this->sqlStateOf($this->insertSql(), $this->newRow($this->devA, $this->unitA1, $this->personA)),
        );
        $this->assertSame(0, $this->attempt("UPDATE occupancies SET ends_on = '2026-09-01' WHERE development_id = {$this->devA}"));
        $this->assertSame(0, $this->attempt("DELETE FROM occupancies WHERE development_id = {$this->devA}"));
        $this->assertRowsUntouched();
    }

    public function testAnArchivedDevelopmentCanBeReadButNotWrittenByItsStaff(): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
        $this->actAs($this->adminA);

        $this->assertSame([$this->row['a1'], $this->row['a2']], $this->readable());
        $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql(), $this->newRow($this->devA, $this->unitA1, $this->personA)));
        $this->assertSame(0, $this->attempt("UPDATE occupancies SET ends_on = '2026-09-01' WHERE id = {$this->row['a1']}"));
        $this->assertRowsUntouched();
    }

    public function testTheSuperAdministratorWritesAnywhere(): void
    {
        $this->actAs($this->superUser);

        $this->assertAllowed($this->insertSql(), $this->newRow($this->devB, $this->unitB, $this->personShared));
        $this->assertAllowed("UPDATE occupancies SET ends_on = '2026-09-01' WHERE id = {$this->row['b1']}");
        $this->assertAllowed("UPDATE occupancies SET ends_on = '2026-09-01' WHERE id = {$this->row['a1']}");
    }

    public function testOnlyTheSuperAdministratorDeletes(): void
    {
        foreach (['adminA', 'dataEntryA', 'accountantA', 'adminB'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame(0, $this->attempt('DELETE FROM occupancies'), $user);
            $this->assertRowsUntouched();
        }
        $this->actAsJob([$this->devA, $this->devB]);
        $this->assertSame(0, $this->attempt('DELETE FROM occupancies'));
        $this->assertRowsUntouched();

        $this->actAs($this->superUser);
        $this->assertAllowed("DELETE FROM occupancies WHERE development_id = {$this->devA}");
        $this->commitSession();
        $this->assertSame(
            [[$this->row['b1']]],
            array_map(static fn(array $r): array => [(int)$r[0]], $this->db()->execute('SELECT id FROM occupancies')->fetchAll()),
        );
    }

    public function testAUnitOrPersonOfAnotherDevelopmentCannotBeUsedAndTheAnswerHidesWhetherItExists(): void
    {
        $this->actAs($this->dataEntryA);

        // A unit of B, and one that does not exist, fail the same constraint.
        $foreignUnit = $this->failure($this->insertSql(), $this->newRow($this->devA, $this->unitB, $this->personShared));
        $missingUnit = $this->failure($this->insertSql(), $this->newRow($this->devA, $this->unitB + 1000, $this->personShared));
        $this->assertSame([self::FOREIGN_KEY, 'occupancies_unit_fkey'], $foreignUnit);
        $this->assertSame($foreignUnit, $missingUnit);

        // A person linked only to B, and one that does not exist, fail the same constraint (AD9).
        $foreignPerson = $this->failure($this->insertSql(), $this->newRow($this->devA, $this->unitA1, $this->personB));
        $missingPerson = $this->failure($this->insertSql(), $this->newRow($this->devA, $this->unitA1, $this->personB + 1000));
        $this->assertSame([self::FOREIGN_KEY, 'occupancies_person_development_fkey'], $foreignPerson);
        $this->assertSame($foreignPerson, $missingPerson);
        // Nor can the row be written into B to use B's person.
        $this->assertSame(self::DENIED, $this->sqlStateOf($this->insertSql(), $this->newRow($this->devB, $this->unitB, $this->personB)));
        // A person of the development, whom the user may not even have met, works: the link is what counts.
        $this->assertAllowed($this->insertSql(), $this->newRow($this->devA, $this->unitA1, $this->personShared));
    }

    public function testTheExclusionConstraintsHoldForStaffToo(): void
    {
        $this->actAs($this->dataEntryA);

        // `a1` is the open-ended primary owner of unit A1 from 2026-01-01.
        $primary = "INSERT INTO occupancies (development_id, unit_id, person_id, role, is_primary, starts_on, created, modified)
                    VALUES (?, ?, ?, 'owner', true, '2026-06-01', NOW(), NOW())";
        $this->assertSame(
            [self::EXCLUSION, 'occupancies_one_primary_per_role'],
            $this->failure($primary, $this->newRow($this->devA, $this->unitA1, $this->personA)),
        );
        $same = "INSERT INTO occupancies (development_id, unit_id, person_id, role, is_primary, starts_on, created, modified)
                 VALUES (?, ?, ?, 'owner', false, '2026-06-01', NOW(), NOW())";
        $this->assertSame(
            [self::EXCLUSION, 'occupancies_no_overlap_per_person'],
            $this->failure($same, $this->newRow($this->devA, $this->unitA1, $this->personResident)),
        );
        // A co-owner is fine.
        $this->assertAllowed($same, $this->newRow($this->devA, $this->unitA1, $this->personA));
    }

    public function testStaffInsertTheWholeRowAndGetItBack(): void
    {
        $this->actAs($this->adminA);

        $id = $this->ids(
            "INSERT INTO occupancies (development_id, unit_id, person_id, role, is_primary, starts_on, ends_on, created, modified)
             VALUES (?, ?, ?, 'tenant', true, '2026-03-01', '2026-04-01', NOW(), NOW()) RETURNING id",
            $this->newRow($this->devA, $this->unitA1, $this->personA),
        );
        $this->assertCount(1, $id);
        $this->assertSame(
            ['tenant', true, '2026-03-01', '2026-04-01'],
            (function () use ($id): array {
                $r = $this->appDb()->execute('SELECT role, is_primary, starts_on::text, ends_on::text FROM occupancies WHERE id = ?', [$id[0]])->fetch();

                return [$r[0], (bool)$r[1], $r[2], $r[3]];
            })(),
        );
    }
}
