<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use PDOException;
use Throwable;

/**
 * Who may change a unit's payer and who may read its history (spec occupancies: Responsible payer, Payer change
 * history, Who may read and write, Database enforcement; design AD11, A1, A6, S-2; tasks 13.3 and 13.5).
 *
 * The payer changes only through the definer function `unit_set_payer`: `super` or `occupancies.manage` in the unit's
 * development (administrator and data_entry), with the same answer for a missing and a foreign unit. History reads
 * follow `units.view` (A6), nobody but the definer writes it. Every denial is checked twice: the answer, and the owner
 * connection showing the payer and the history unchanged after the session is committed.
 */
class RlsUnitPayerTest extends RlsTestCase
{
    /**
     * SQLSTATE of a policy violation or a missing privilege (insufficient_privilege).
     */
    private const DENIED = '42501';

    private const INVALID = '22023';

    private int $supervisorA;

    private int $observer;

    private int $unitA;

    private int $unitA2;

    private int $unitB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisorA = $this->createUser('supervisor_a', null);
        $this->assignRole($this->supervisorA, 'supervisor', $this->devA);
        $this->observer = $this->createUser('observer', null);
        $this->assignRole($this->observer, 'administrator', $this->devA, -30, null, true);
        $this->unitA = $this->seedUnit($this->devA, null, '101');
        $this->unitA2 = $this->seedUnit($this->devA, null, '102');
        $this->unitB = $this->seedUnit($this->devB, null, '201');
    }

    /**
     * Call the function as the runtime role.
     *
     * @param mixed $unit Unit id.
     * @param mixed $payer Payer.
     * @return array{string|null, string|null} SQLSTATE and message, both null on success.
     */
    private function setPayer(mixed $unit, mixed $payer): array
    {
        $connection = $this->appDb();
        $connection->execute('SAVEPOINT set_payer');
        try {
            $connection->execute('SELECT public.unit_set_payer(?, ?)', [$unit, $payer])->fetchAll();
            $connection->execute('RELEASE SAVEPOINT set_payer');

            return [null, null];
        } catch (Throwable $e) {
            $connection->execute('ROLLBACK TO SAVEPOINT set_payer');
            for (; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                    return [(string)$e->errorInfo[0], (string)$e->errorInfo[2]];
                }
            }

            return ['unknown', null];
        }
    }

    /**
     * @return array<int, string> Payer of every unit, as the owner stored it.
     */
    private function payers(): array
    {
        $rows = $this->db()->execute('SELECT id, payer FROM units ORDER BY id')->fetchAll();

        return array_column(array_map(static fn(array $r): array => [(int)$r[0], $r[1]], $rows), 1, 0);
    }

    /**
     * @return list<array{int, string, int|null}> Unit, payer and acting user of every history row, in order.
     */
    private function history(): array
    {
        return array_map(
            static fn(array $r): array => [(int)$r[0], $r[1], $r[2] === null ? null : (int)$r[2]],
            $this->db()->execute('SELECT unit_id, payer, changed_by_user_id FROM unit_payer_changes ORDER BY id')->fetchAll(),
        );
    }

    /**
     * @return list<array{int, string, int|null}> The history right after the fixtures: one `owner` row per unit.
     */
    private function seededHistory(): array
    {
        return [[$this->unitA, 'owner', null], [$this->unitA2, 'owner', null], [$this->unitB, 'owner', null]];
    }

    private function assertNothingChanged(): void
    {
        $this->commitSession();
        $this->assertSame(
            [$this->unitA => 'owner', $this->unitA2 => 'owner', $this->unitB => 'owner'],
            $this->payers(),
        );
        $this->assertSame($this->seededHistory(), $this->history());
    }

    public function testTheFunctionIsADefinerWithAPinnedSearchPathCallableOnlyByTheRuntimeRole(): void
    {
        $row = $this->db()->execute(
            "SELECT pg_get_userbyid(proowner), prosecdef, proconfig::text, proacl::text, provolatile,
                    has_function_privilege('kualitet_app', oid, 'EXECUTE'), has_function_privilege('public', oid, 'EXECUTE'),
                    pg_get_function_identity_arguments(oid), prorettype::regtype::text
             FROM pg_proc WHERE proname = 'unit_set_payer' AND pronamespace = 'public'::regnamespace",
        )->fetch();

        $this->assertSame('kualitet_definer', $row[0]);
        $this->assertTrue((bool)$row[1], 'SECURITY DEFINER');
        $this->assertSame('{"search_path=pg_catalog, pg_temp"}', $row[2]);
        $this->assertSame('{kualitet_definer=X/kualitet_definer,kualitet_app=X/kualitet_definer}', $row[3]);
        $this->assertSame('v', $row[4]);
        $this->assertTrue((bool)$row[5]);
        $this->assertFalse((bool)$row[6]);
        $this->assertSame('p_unit_id bigint, p_payer text', $row[7]);
        $this->assertSame('void', $row[8]);
    }

    public function testTheSuperAdministratorAdministratorAndDataEntryChangeThePayerOfTheirUnits(): void
    {
        $cases = [
            'superUser' => $this->unitB,
            'adminA' => $this->unitA,
            'dataEntryA' => $this->unitA2,
        ];
        $expected = [];
        foreach ($cases as $user => $unit) {
            $this->actAs($this->{$user});
            $this->assertSame([null, null], $this->setPayer($unit, 'tenant'), $user);
            $this->commitSession();
            $expected[] = [$unit, 'tenant', $this->{$user}];
        }

        $this->assertSame([$this->unitA => 'tenant', $this->unitA2 => 'tenant', $this->unitB => 'tenant'], $this->payers());
        // The seeded rows come first; each change adds one row that names the acting user.
        $this->assertSame([...$this->seededHistory(), ...$expected], $this->history());
    }

    public function testTheSuperAdministratorMayChangeAnyDevelopmentButARoleOfAnotherOneMayNot(): void
    {
        $this->actAs($this->adminB);
        $this->assertSame([null, null], $this->setPayer($this->unitB, 'tenant'));
        $this->assertSame(self::DENIED, $this->setPayer($this->unitA, 'tenant')[0]);
        $this->commitSession();

        $this->assertSame('owner', $this->payers()[$this->unitA]);
        $this->assertSame('tenant', $this->payers()[$this->unitB]);
    }

    public function testEveryOtherActorIsRefusedAndNothingChanges(): void
    {
        $users = ['accountantA', 'supervisorA', 'resident', 'guardA', 'disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner', 'observer'];
        foreach ($users as $user) {
            $this->actAs($this->{$user});
            $this->assertSame(self::DENIED, $this->setPayer($this->unitA, 'tenant')[0], $user);
            $this->assertNothingChanged();
        }
    }

    public function testAJobAndAnAnonymousRequestAreRefused(): void
    {
        $this->actAsJob([$this->devA, $this->devB]);
        $this->assertSame(self::DENIED, $this->setPayer($this->unitA, 'tenant')[0], 'job');
        $this->assertNothingChanged();

        $this->actAs(null);
        $this->assertSame(self::DENIED, $this->setPayer($this->unitA, 'tenant')[0], 'anonymous');
        $this->assertNothingChanged();
    }

    public function testAMissingAndAForeignUnitGiveTheSameAnswer(): void
    {
        $missing = $this->unitB + 1000;
        $this->actAs($this->adminA);

        $foreign = $this->setPayer($this->unitB, 'tenant');
        $this->assertSame(self::DENIED, $foreign[0]);
        $this->assertSame($foreign, $this->setPayer($missing, 'tenant'));
        $this->assertSame($foreign, $this->setPayer(0, 'tenant'));
        $this->assertSame($foreign, $this->setPayer(null, 'tenant'));
        // The payer is not looked at before the unit is: a bad payer says nothing about the unit either.
        $this->assertSame($foreign, $this->setPayer($this->unitB, 'landlord'));
        $this->assertSame($foreign, $this->setPayer($missing, 'landlord'));
        $this->assertSame($foreign, $this->setPayer($this->unitB, null));
        $this->assertStringNotContainsString((string)$this->unitB, (string)$foreign[1]);
        $this->assertNothingChanged();

        // Every one of the refused roles also gets that very answer for a unit of its own development.
        $this->actAs($this->accountantA);
        $this->assertSame($foreign, $this->setPayer($this->unitA, 'tenant'));
        $this->endSession();
    }

    public function testAnInvalidPayerIsRejectedForAnAuthorizedCaller(): void
    {
        $this->actAs($this->dataEntryA);
        foreach (['landlord', '', 'OWNER', ' owner', "tenant\n", null] as $payer) {
            $this->assertSame(self::INVALID, $this->setPayer($this->unitA, $payer)[0], json_encode($payer));
        }
        $this->assertNothingChanged();
    }

    public function testSettingTheCurrentPayerAgainChangesNothingAndLogsNothing(): void
    {
        $modified = fn(): string => (string)$this->db()->execute('SELECT modified::text FROM units WHERE id = ?', [$this->unitA])->fetch()[0];
        $before = $modified();

        $this->actAs($this->adminA);
        $this->assertSame([null, null], $this->setPayer($this->unitA, 'owner'));
        $this->assertNothingChanged();
        // The row was not even touched: a no-op leaves `modified` alone.
        $this->assertSame($before, $modified());
    }

    public function testChangingBackAndForthLogsEveryChangeInOrderWithTheActingUser(): void
    {
        $this->actAs($this->adminA);
        $this->setPayer($this->unitA, 'tenant');
        $this->setPayer($this->unitA, 'owner');
        $this->setPayer($this->unitA, 'tenant');
        $this->commitSession();

        $this->assertSame(
            [...$this->seededHistory(), [$this->unitA, 'tenant', $this->adminA], [$this->unitA, 'owner', $this->adminA], [$this->unitA, 'tenant', $this->adminA]],
            $this->history(),
        );
        // The clock is the database's: the time of each row is not before the one of the previous row.
        $this->assertTrue((bool)$this->db()->execute(
            "SELECT bool_and(changed_at >= prev) FROM (
                 SELECT changed_at, lag(changed_at) OVER (ORDER BY id) AS prev FROM unit_payer_changes WHERE unit_id = ?
             ) t WHERE prev IS NOT NULL",
            [$this->unitA],
        )->fetch()[0]);
    }

    public function testTheRuntimeRoleCannotWriteThePayerDirectly(): void
    {
        foreach (['superUser', 'adminA', 'dataEntryA'] as $user) {
            $this->actAs($this->{$user});

            $this->assertSame(self::DENIED, $this->sqlStateOf("UPDATE units SET payer = 'tenant' WHERE id = {$this->unitA}"), $user);
            $this->assertSame(self::DENIED, $this->sqlStateOf("UPDATE units SET payer = 'tenant'"), $user);
            $this->assertSame(
                self::DENIED,
                $this->sqlStateOf(
                    "INSERT INTO units (development_id, number, type, payer, created, modified)
                     VALUES ({$this->devA}, '900', 'house', 'tenant', NOW(), NOW())",
                ),
                $user,
            );
            $this->assertNothingChanged();
        }
    }

    public function testAUnitCreatedByTheRuntimeRoleStartsAsOwnerWithOneRowNamingItsCreator(): void
    {
        $this->actAs($this->adminA);
        $id = (int)$this->appDb()->execute(
            "INSERT INTO units (development_id, number, type, created, modified)
             VALUES (?, '900', 'house', NOW(), NOW()) RETURNING id",
            [$this->devA],
        )->fetch()[0];
        $this->commitSession();

        $this->assertSame('owner', $this->payers()[$id]);
        $this->assertSame([...$this->seededHistory(), [$id, 'owner', $this->adminA]], $this->history());
    }

    private function archiveDevelopmentA(): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
    }

    public function testStaffOfAnArchivedDevelopmentGetTheSameRefusalAndNothingChanges(): void
    {
        $this->archiveDevelopmentA();
        $this->actAs($this->loner);
        $refusal = $this->setPayer($this->unitA, 'tenant');
        $this->endSession();
        $this->assertSame(self::DENIED, $refusal[0]);

        foreach (['adminA', 'dataEntryA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame($refusal, $this->setPayer($this->unitA, 'tenant'), $user);
            $this->assertNothingChanged();
        }
    }

    public function testTheSuperAdministratorStillChangesThePayerInAnArchivedDevelopment(): void
    {
        // Pins the database behavior (design A3 and A6): `permission_dev_ids` drops an archived development for staff
        // but `is_super()` is not narrowed. Layer 1 (PR 5c) must refuse this write for everyone.
        $this->archiveDevelopmentA();

        $this->actAs($this->superUser);
        $this->assertSame([null, null], $this->setPayer($this->unitA, 'tenant'));
        $this->commitSession();

        $this->assertSame('tenant', $this->payers()[$this->unitA]);
        $this->assertSame([...$this->seededHistory(), [$this->unitA, 'tenant', $this->superUser]], $this->history());
    }

    public function testTheHistoryOfAnArchivedDevelopmentIsStillRead(): void
    {
        $this->archiveDevelopmentA();

        foreach (['adminA', 'accountantA', 'supervisorA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->unitA, $this->unitA2], $this->ids('SELECT unit_id FROM unit_payer_changes'), $user);
            $this->endSession();
        }
        $this->actAs($this->superUser);
        $this->assertSame([$this->unitA, $this->unitA2, $this->unitB], $this->ids('SELECT unit_id FROM unit_payer_changes'));
    }

    /**
     * @param string $code Role code.
     * @param list<string> $permissions Permission codes the role holds.
     * @return int User with that one role in development A.
     */
    private function probeUser(string $code, array $permissions): int
    {
        $this->db()->execute('INSERT INTO roles (code, name) VALUES (?, ?)', [$code, $code]);
        foreach ($permissions as $permission) {
            $this->db()->execute(
                'INSERT INTO role_permissions (role_id, permission_id)
                 SELECT r.id, p.id FROM roles r, permissions p WHERE r.code = ? AND p.code = ?',
                [$code, $permission],
            );
        }
        $user = $this->createUser($code . '_user', null);
        $this->assignRole($user, $code, $this->devA);

        return $user;
    }

    private function dropProbeRoles(): void
    {
        $this->db()->execute("DELETE FROM role_assignments WHERE role_id IN (SELECT id FROM roles WHERE code LIKE 'probe_%')");
        $this->db()->execute("DELETE FROM roles WHERE code LIKE 'probe_%'");
    }

    public function testOnlyTheOccupanciesManagePermissionAuthorizesAPayerChange(): void
    {
        try {
            // Holds occupancies.manage and nothing else: allowed (the function is a definer, it needs no read right).
            $manager = $this->probeUser('probe_occupancies', ['occupancies.manage']);
            // Holds every neighboring permission but not occupancies.manage: refused.
            $neighbor = $this->probeUser(
                'probe_neighbor',
                ['units.view', 'units.manage', 'people.view', 'people.manage', 'developments.view'],
            );

            $this->actAs($neighbor);
            $this->assertSame(self::DENIED, $this->setPayer($this->unitA, 'tenant')[0]);
            $this->assertNothingChanged();

            $this->actAs($manager);
            $this->assertSame([null, null], $this->setPayer($this->unitA, 'tenant'));
            $this->assertSame(self::DENIED, $this->setPayer($this->unitB, 'tenant')[0]);
            $this->commitSession();
            $this->assertSame('tenant', $this->payers()[$this->unitA]);
            $this->assertSame('owner', $this->payers()[$this->unitB]);
        } finally {
            $this->dropProbeRoles();
        }
    }

    public function testARealChangeTouchesModifiedAndANoOpDoesNot(): void
    {
        $modified = fn(): string => (string)$this->db()->execute('SELECT modified::text FROM units WHERE id = ?', [$this->unitA])->fetch()[0];
        $before = $modified();

        $this->actAs($this->adminA);
        $this->setPayer($this->unitA, 'tenant');
        $this->commitSession();

        $this->assertNotSame($before, $modified());
    }

    public function testHistoryReadsFollowUnitsViewInTheDevelopmentOfTheRow(): void
    {
        // Rows by development: A has two units, B one.
        foreach (['adminA', 'accountantA', 'dataEntryA', 'supervisorA', 'observer'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->unitA, $this->unitA2], $this->ids('SELECT unit_id FROM unit_payer_changes'), $user);
            $this->endSession();
        }
        $this->actAs($this->adminB);
        $this->assertSame([$this->unitB], $this->ids('SELECT unit_id FROM unit_payer_changes'));
        $this->endSession();

        // They hold an assignment in A but their role lacks units.view.
        foreach (['resident', 'guardA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->devA], $this->ids('SELECT unnest(dev_ids())'), $user);
            $this->assertSame([], $this->ids('SELECT unit_id FROM unit_payer_changes'), $user);
            $this->endSession();
        }
        foreach (['disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([], $this->ids('SELECT unit_id FROM unit_payer_changes'), $user);
            $this->endSession();
        }
        $this->actAsJob([$this->devA, $this->devB]);
        $this->assertSame([], $this->ids('SELECT unit_id FROM unit_payer_changes'), 'job');
        $this->endSession();
        $this->actAs(null);
        $this->assertSame([], $this->ids('SELECT unit_id FROM unit_payer_changes'), 'anonymous');
        $this->endSession();

        $this->actAs($this->superUser);
        $this->assertSame([$this->unitA, $this->unitA2, $this->unitB], $this->ids('SELECT unit_id FROM unit_payer_changes'));
    }

    public function testNoActorWritesTheHistoryNotEvenTheSuperAdministrator(): void
    {
        $insert = "INSERT INTO unit_payer_changes (development_id, unit_id, payer, changed_at) VALUES (?, ?, 'tenant', NOW())";

        foreach (['superUser', 'adminA', 'dataEntryA', 'adminB'] as $user) {
            $this->actAs($this->{$user});

            $this->assertSame(self::DENIED, $this->sqlStateOf($insert, [$this->devA, $this->unitA]), $user);
            $this->assertSame(self::DENIED, $this->sqlStateOf($insert, [$this->devB, $this->unitB]), $user);
            $this->assertSame(self::DENIED, $this->sqlStateOf("UPDATE unit_payer_changes SET payer = 'tenant'"), $user);
            $this->assertSame(self::DENIED, $this->sqlStateOf('UPDATE unit_payer_changes SET changed_by_user_id = NULL'), $user);
            $this->assertSame(self::DENIED, $this->sqlStateOf('DELETE FROM unit_payer_changes'), $user);
            $this->assertSame(self::DENIED, $this->sqlStateOf('TRUNCATE unit_payer_changes'), $user);
            $this->assertNothingChanged();
        }
    }

    public function testTheHistoryTableIsForcedAndHasOnlyASelectPolicyThatFollowsUnitsView(): void
    {
        $flags = $this->db()->execute(
            "SELECT relrowsecurity, relforcerowsecurity FROM pg_class WHERE oid = 'unit_payer_changes'::regclass",
        )->fetch();
        $this->assertTrue((bool)$flags[0]);
        $this->assertTrue((bool)$flags[1]);

        $policies = $this->db()->execute(
            "SELECT policyname, cmd, roles::text, qual, with_check FROM pg_policies WHERE tablename = 'unit_payer_changes'",
        )->fetchAll();
        $this->assertCount(1, $policies);
        [$name, $command, $roles, $qual, $check] = $policies[0];
        $this->assertSame(['unit_payer_changes_select', 'SELECT', '{kualitet_app}', null], [$name, $command, $roles, $check]);
        $this->assertStringContainsString("permission_dev_ids('units.view'::text)", $qual);
        $this->assertStringContainsString('is_super()', $qual);
        // Neither the plain assignment list nor the write code decides who reads it.
        $this->assertStringNotContainsString('dev_ids() AS', $qual);
        $this->assertStringNotContainsString('occupancies.manage', $qual);
        $this->assertStringNotContainsString('person_id', $qual);
    }
}
