<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * permission_dev_ids(code): the developments where the session may exercise a permission (AD7, ADR-0005 section 1).
 *
 * Written against codes that are seeded already: `payments.create` (a write code held by administrator, accountant
 * and data_entry) and `reports.view` (a read code held by administrator, accountant and supervisor).
 */
class RlsPermissionDevIdsTest extends RlsTestCase
{
    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function writeCodeHolders(): array
    {
        return [
            'administrator of A' => ['adminA', ['A']],
            'accountant of A' => ['accountantA', ['A']],
            'data entry of A' => ['dataEntryA', ['A']],
            'administrator of B' => ['adminB', ['B']],
            'guard of A (role lacks the code)' => ['guardA', []],
            'resident of A (role lacks the code)' => ['resident', []],
            'super administrator (covered by is_super, no development rows)' => ['superUser', []],
            'disabled administrator' => ['disabledAdmin', []],
            'ended assignment' => ['endedAdmin', []],
            'future assignment' => ['futureAdmin', []],
            'user without assignments' => ['loner', []],
        ];
    }

    /**
     * @param string $user Property holding the user id.
     * @param list<string> $expected Developments expected.
     * @return void
     */
    #[DataProvider('writeCodeHolders')]
    public function testAWriteCodeFollowsTheAssignmentsWhoseRoleHoldsIt(string $user, array $expected): void
    {
        $this->actAs($this->{$user});

        $this->assertSame($this->resolve($expected), $this->permissionDevIds('payments.create'));
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function readCodeHolders(): array
    {
        return [
            'administrator of A' => ['adminA', ['A']],
            'accountant of A' => ['accountantA', ['A']],
            'data entry of A (role lacks the code)' => ['dataEntryA', []],
            'administrator of B' => ['adminB', ['B']],
            'resident of A (role lacks the code)' => ['resident', []],
            'ended assignment' => ['endedAdmin', []],
        ];
    }

    /**
     * @param string $user Property holding the user id.
     * @param list<string> $expected Developments expected.
     * @return void
     */
    #[DataProvider('readCodeHolders')]
    public function testAReadCodeFollowsTheAssignmentsWhoseRoleHoldsIt(string $user, array $expected): void
    {
        $this->actAs($this->{$user});

        $this->assertSame($this->resolve($expected), $this->permissionDevIds('reports.view'));
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function unitsManageHolders(): array
    {
        return [
            'administrator of A' => ['adminA', ['A']],
            'data entry of A' => ['dataEntryA', ['A']],
            'administrator of B' => ['adminB', ['B']],
            'accountant of A (role lacks the code)' => ['accountantA', []],
            'guard of A (role lacks the code)' => ['guardA', []],
            'resident of A (role lacks the code)' => ['resident', []],
        ];
    }

    /**
     * @param string $user Property holding the user id.
     * @param list<string> $expected Developments expected.
     * @return void
     */
    #[DataProvider('unitsManageHolders')]
    public function testUnitsManageFollowsTheSeededRoleRows(string $user, array $expected): void
    {
        $this->actAs($this->{$user});

        $this->assertSame($this->resolve($expected), $this->permissionDevIds('units.manage'));
    }

    public function testUnitsManageLeavesAnArchivedDevelopment(): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
        $this->actAs($this->dataEntryA);

        $this->assertSame([], $this->permissionDevIds('units.manage'));
    }

    public function testAReadOnlyAssignmentKeepsReadCodesAndLosesWriteCodes(): void
    {
        $user = $this->createUser('observer', null);
        $this->assignRole($user, 'accountant', $this->devA, -30, null, true);
        $this->actAs($user);

        $this->assertSame([$this->devA], $this->permissionDevIds('reports.view'));
        $this->assertSame([], $this->permissionDevIds('payments.create'));
    }

    public function testAWritableAssignmentKeepsBothKindsOfCode(): void
    {
        $this->actAs($this->accountantA);

        $this->assertSame([$this->devA], $this->permissionDevIds('reports.view'));
        $this->assertSame([$this->devA], $this->permissionDevIds('payments.create'));
    }

    public function testAnArchivedDevelopmentDropsWriteCodesButKeepsReadCodes(): void
    {
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devA]);
        $this->actAs($this->accountantA);

        $this->assertSame([], $this->permissionDevIds('payments.create'));
        $this->assertSame([$this->devA], $this->permissionDevIds('reports.view'));
    }

    public function testOnlyTheArchivedDevelopmentIsDroppedFromAWriteCode(): void
    {
        $user = $this->createUser('two_devs', null);
        $this->assignRole($user, 'accountant', $this->devA);
        $this->assignRole($user, 'accountant', $this->devB);
        $this->db()->execute('UPDATE developments SET archived_at = NOW() WHERE id = ?', [$this->devB]);
        $this->actAs($user);

        $this->assertSame([$this->devA], $this->permissionDevIds('payments.create'));
        $this->assertSame([$this->devA, $this->devB], $this->permissionDevIds('reports.view'));
    }

    public function testSeveralAssignmentsToTheSameDevelopmentListItOnce(): void
    {
        $user = $this->createUser('two_roles', null);
        $this->assignRole($user, 'administrator', $this->devA);
        $this->assignRole($user, 'accountant', $this->devA);
        $this->actAs($user);

        $this->assertSame([$this->devA], $this->permissionDevIds('payments.create'));
    }

    public function testAnAssignmentOnTheLastDayStillCountsAndOneThatStartsTodayCounts(): void
    {
        $lastDay = $this->createUser('last_day', null);
        $this->assignRole($lastDay, 'accountant', $this->devA, -30, 0);
        $firstDay = $this->createUser('first_day', null);
        $this->assignRole($firstDay, 'accountant', $this->devB, 0);

        $this->actAs($lastDay);
        $this->assertSame([$this->devA], $this->permissionDevIds('payments.create'));
        $this->endSession();

        $this->actAs($firstDay);
        $this->assertSame([$this->devB], $this->permissionDevIds('payments.create'));
    }

    public function testAnUnknownOrMalformedCodeGrantsNothing(): void
    {
        $this->actAs($this->adminA);

        $this->assertSame([], $this->permissionDevIds('payments.nonexistent'));
        $this->assertSame([], $this->permissionDevIds(''));
        $this->assertSame([], $this->permissionDevIds("payments.create' OR '1'='1"));
        // The control: the same session does hold the real code.
        $this->assertSame([$this->devA], $this->permissionDevIds('payments.create'));
    }

    public function testASuperAdministratorAssignmentAddsNoDevelopmentRows(): void
    {
        $this->actAs($this->superUser);

        // is_super() carries the super-administrator in the policies; the function lists development grants only.
        $this->assertTrue((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
        $this->assertSame([], $this->permissionDevIds('payments.create'));
        $this->assertSame([], $this->permissionDevIds('reports.view'));
    }

    public function testNoSessionMeansNoDevelopments(): void
    {
        $this->actAs(null);

        $this->assertSame([], $this->permissionDevIds('payments.create'));
        $this->assertSame([], $this->permissionDevIds('reports.view'));
    }

    public function testAJobContextGetsNothingEvenForReadCodes(): void
    {
        // The job helper dev_ids() would list devA; the permission helper must not follow it.
        $this->actAsJob([$this->devA]);

        $this->assertSame([$this->devA], $this->ids('SELECT unnest(dev_ids())'));
        $this->assertSame([], $this->permissionDevIds('payments.create'));
        $this->assertSame([], $this->permissionDevIds('reports.view'));
    }

    public function testAUserIdWithoutTheRequestContextGetsNothing(): void
    {
        $this->appDb()->begin();
        $this->appDb()->execute(
            "SELECT set_config('app.context', 'webhook', true), set_config('app.user_id', ?, true)",
            [(string)$this->adminA],
        );

        $this->assertSame([], $this->permissionDevIds('payments.create'));
    }

    public function testARevokedAssignmentTakesEffectOnTheNextTransaction(): void
    {
        $this->actAs($this->accountantA);
        $this->assertSame([$this->devA], $this->permissionDevIds('payments.create'));
        $this->endSession();

        $this->db()->execute(
            'UPDATE role_assignments SET ends_on = ? WHERE user_id = ?',
            [$this->businessDay(-1), $this->accountantA],
        );

        $this->actAs($this->accountantA);
        $this->assertSame([], $this->permissionDevIds('payments.create'));
    }

    public function testTheRuntimeRoleMayExecuteItAndPublicMayNot(): void
    {
        $row = $this->db()->execute(
            "SELECT p.prosecdef, p.provolatile, pg_get_userbyid(p.proowner),
                    has_function_privilege('kualitet_app', p.oid, 'EXECUTE'),
                    has_function_privilege('public', p.oid, 'EXECUTE'),
                    array_to_string(p.proconfig, ',')
             FROM pg_proc p
             WHERE p.pronamespace = 'public'::regnamespace AND p.proname = 'permission_dev_ids'",
        )->fetch();

        $this->assertNotFalse($row, 'permission_dev_ids() does not exist');
        $this->assertTrue((bool)$row[0], 'must be SECURITY DEFINER');
        $this->assertSame('s', $row[1], 'must be STABLE');
        $this->assertSame('kualitet_definer', $row[2]);
        $this->assertTrue((bool)$row[3]);
        $this->assertFalse((bool)$row[4]);
        $this->assertSame('search_path=pg_catalog, pg_temp', $row[5]);
    }

    /**
     * @param list<string> $keys `A` and `B`.
     * @return list<int>
     */
    private function resolve(array $keys): array
    {
        $ids = ['A' => $this->devA, 'B' => $this->devB];

        return array_map(static fn(string $k): int => $ids[$k], $keys);
    }

    /**
     * @param string $code Permission code.
     * @return list<int>
     */
    private function permissionDevIds(string $code): array
    {
        return $this->ids('SELECT unnest(permission_dev_ids(?))', [$code]);
    }
}
