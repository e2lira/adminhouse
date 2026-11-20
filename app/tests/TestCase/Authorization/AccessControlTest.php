<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authorization;

use App\Clock\BusinessClock;
use App\Test\Support\ActingAccessControl;
use App\Test\Support\DatabaseTestCase;
use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Which developments a user may act on for a permission (RB-R3, RB-R4, ADR-0005).
 */
class AccessControlTest extends DatabaseTestCase
{
    private ActingAccessControl $access;

    protected function setUp(): void
    {
        parent::setUp();
        $this->access = new ActingAccessControl();
    }

    public function testSuperAdministratorGetsEveryDevelopment(): void
    {
        $userId = $this->createUser('root');
        $this->assignRole($userId, 'super_administrator', null);

        $scope = $this->access->allowedFor($userId, 'payments.create');

        $this->assertTrue($scope->isAll());
        $this->assertFalse($scope->isEmpty());
        $this->assertTrue($scope->allows($this->createDevelopment('Created after the account')));
    }

    public function testSuperAdministratorGetsAnyPermissionEvenOneNotInTheCatalogue(): void
    {
        $userId = $this->createUser('root');
        $this->assignRole($userId, 'super_administrator', null);

        $this->assertTrue($this->access->allowedFor($userId, 'invented.permission')->isAll());
    }

    public function testScopedUserOnlySeesTheirDevelopment(): void
    {
        $a = $this->createDevelopment('A');
        $this->createDevelopment('B');
        $userId = $this->createUser('ann');
        $this->assignRole($userId, 'administrator', $a);

        $scope = $this->access->allowedFor($userId, 'developments.view');

        $this->assertFalse($scope->isAll());
        $this->assertSame([$a], $scope->ids());
    }

    public function testPermissionsAreEvaluatedPerDevelopment(): void
    {
        $a = $this->createDevelopment('A');
        $b = $this->createDevelopment('B');
        $userId = $this->createUser('ann');
        $this->assignRole($userId, 'supervisor', $a);
        $this->assignRole($userId, 'accountant', $b);

        $this->assertSame([$b], $this->access->allowedFor($userId, 'payments.create')->ids());
        $this->assertSame([$a, $b], $this->access->allowedFor($userId, 'reports.view')->ids());
    }

    public function testExpiredAssignmentGrantsNothing(): void
    {
        $a = $this->createDevelopment('A');
        $userId = $this->createUser('ann');
        $this->assignRole($userId, 'administrator', $a, -60, -1);

        $this->assertTrue($this->access->allowedFor($userId, 'developments.view')->isEmpty());
    }

    public function testFutureAssignmentGrantsNothing(): void
    {
        $a = $this->createDevelopment('A');
        $userId = $this->createUser('ann');
        $this->assignRole($userId, 'administrator', $a, 1, 30);

        $this->assertTrue($this->access->allowedFor($userId, 'developments.view')->isEmpty());
    }

    public function testAssignmentIsActiveOnItsFirstAndLastDay(): void
    {
        $a = $this->createDevelopment('A');
        $b = $this->createDevelopment('B');
        $userId = $this->createUser('ann');
        $this->assignRole($userId, 'administrator', $a, 0, 5);
        $this->assignRole($userId, 'administrator', $b, -5, 0);

        $this->assertSame([$a, $b], $this->access->allowedFor($userId, 'developments.view')->ids());
    }

    public function testRoleWithoutThePermissionGrantsNothing(): void
    {
        $a = $this->createDevelopment('A');
        $userId = $this->createUser('gary');
        $this->assignRole($userId, 'guard', $a);

        $this->assertTrue($this->access->allowedFor($userId, 'payments.create')->isEmpty());
        $this->assertSame([$a], $this->access->allowedFor($userId, 'gate.log')->ids());
    }

    public function testReadOnlyAssignmentOnlyGrantsViewPermissions(): void
    {
        $a = $this->createDevelopment('A');
        $userId = $this->createUser('rita');
        $this->assignRole($userId, 'accountant', $a, -30, null, true);

        $this->assertSame([$a], $this->access->allowedFor($userId, 'reports.view')->ids());
        $this->assertTrue($this->access->allowedFor($userId, 'payments.create')->isEmpty());
    }

    public function testDisabledUserGetsNothingEvenAsSuperAdministrator(): void
    {
        $userId = $this->createUser('root', 'correct horse battery', 'disabled');
        $this->assignRole($userId, 'super_administrator', null);

        $this->assertTrue($this->access->allowedFor($userId, 'developments.view')->isEmpty());
    }

    public function testUnknownUserAndUnknownPermissionGetNothing(): void
    {
        $a = $this->createDevelopment('A');
        $userId = $this->createUser('ann');
        $this->assignRole($userId, 'administrator', $a);

        $this->assertTrue($this->access->allowedFor(999999, 'developments.view')->isEmpty());
        $this->assertTrue($this->access->allowedFor($userId, 'no.such.permission')->isEmpty());
    }

    public function testDuplicateAssignmentsDoNotRepeatIds(): void
    {
        $a = $this->createDevelopment('A');
        $userId = $this->createUser('ann');
        $this->assignRole($userId, 'administrator', $a);
        $this->assignRole($userId, 'accountant', $a);

        $this->assertSame([$a], $this->access->allowedFor($userId, 'reports.view')->ids());
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function roleMatrix(): array
    {
        return [
            'administrator' => ['administrator', [
                'developments.view', 'payments.create', 'people.manage', 'people.view', 'reports.view',
                'role_assignments.manage', 'units.manage', 'units.view',
            ]],
            'accountant' => ['accountant', [
                'developments.view', 'payments.create', 'people.view', 'reports.view', 'units.view',
            ]],
            'data_entry' => ['data_entry', [
                'developments.view', 'payments.create', 'people.manage', 'people.view', 'units.manage', 'units.view',
            ]],
            'supervisor' => ['supervisor', ['developments.view', 'people.view', 'reports.view', 'units.view']],
            'guard' => ['guard', ['gate.log']],
            // AD13: a resident has no development-wide unit access until the resident sub-scope exists (D7).
            'resident' => ['resident', []],
        ];
    }

    /**
     * @param string $role Role code.
     * @param list<string> $expected Permission codes the role must hold.
     * @return void
     */
    #[DataProvider('roleMatrix')]
    public function testSeededRoleMatrix(string $role, array $expected): void
    {
        $rows = $this->db()->execute(
            'SELECT p.code FROM role_permissions rp
             JOIN roles r ON r.id = rp.role_id JOIN permissions p ON p.id = rp.permission_id
             WHERE r.code = ? ORDER BY p.code',
            [$role],
        )->fetchAll('assoc');

        $this->assertSame($expected, array_column($rows, 'code'));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function unitsManageByRole(): array
    {
        return [
            'administrator' => ['administrator', true],
            'data_entry' => ['data_entry', true],
            'accountant' => ['accountant', false],
            'supervisor' => ['supervisor', false],
            'guard' => ['guard', false],
            'resident' => ['resident', false],
        ];
    }

    /**
     * @param string $role Role code.
     * @param bool $holds Whether the role may manage units.
     * @return void
     */
    #[DataProvider('unitsManageByRole')]
    public function testUnitsManageIsHeldByAdministratorAndDataEntryOnly(string $role, bool $holds): void
    {
        $dev = $this->createDevelopment('A');
        $other = $this->createDevelopment('B');
        $user = $this->createUser('u_' . $role);
        $this->assignRole($user, $role, $dev);

        $scope = $this->access->allowedFor($user, 'units.manage');

        $this->assertSame($holds ? [$dev] : [], $scope->ids());
        $this->assertFalse($scope->allows($other));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function managedWritePairs(): array
    {
        // Write code => read code of each managed domain table (Rls::managedDomainTable). Add a row with each new one.
        return [
            'units' => ['units.manage', 'units.view'],
            // People are not a managed domain table, but editing one reads it first (the UPDATE policy's WHERE).
            'people' => ['people.manage', 'people.view'],
        ];
    }

    /**
     * PostgreSQL applies the SELECT policy to INSERT ... RETURNING (what the ORM issues) and to the WHERE of an
     * UPDATE, so a role that can write a managed table but not read it can do nothing useful. Seeds keep the two
     * together.
     *
     * @param string $write Write code.
     * @param string $read Read code.
     * @return void
     */
    #[DataProvider('managedWritePairs')]
    public function testEveryRoleHoldingAManagedWriteCodeAlsoHoldsItsReadCode(string $write, string $read): void
    {
        $holders = fn(string $code): array => array_column($this->db()->execute(
            'SELECT r.code FROM role_permissions rp
             JOIN roles r ON r.id = rp.role_id JOIN permissions p ON p.id = rp.permission_id
             WHERE p.code = ? ORDER BY r.code',
            [$code],
        )->fetchAll('assoc'), 'code');

        $writers = $holders($write);
        $this->assertNotSame([], $writers, 'No role holds ' . $write);
        $this->assertSame([], array_values(array_diff($writers, $holders($read))));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function peopleManageByRole(): array
    {
        return [
            'administrator' => ['administrator', true],
            'data_entry' => ['data_entry', true],
            'accountant' => ['accountant', false],
            'supervisor' => ['supervisor', false],
            'guard' => ['guard', false],
            'resident' => ['resident', false],
        ];
    }

    /**
     * @param string $role Role code.
     * @param bool $holds Whether the role may manage people.
     * @return void
     */
    #[DataProvider('peopleManageByRole')]
    public function testPeopleManageIsHeldByAdministratorAndDataEntryOnly(string $role, bool $holds): void
    {
        $dev = $this->createDevelopment('A');
        $other = $this->createDevelopment('B');
        $user = $this->createUser('p_' . $role);
        $this->assignRole($user, $role, $dev);

        $scope = $this->access->allowedFor($user, 'people.manage');

        $this->assertSame($holds ? [$dev] : [], $scope->ids());
        $this->assertFalse($scope->allows($other));
    }

    public function testAResidentOnlyUserIsRefusedPeopleReads(): void
    {
        $dev = $this->createDevelopment('A');
        $user = $this->createUser('tenant_people');
        $this->assignRole($user, 'resident', $dev);

        $this->assertTrue($this->access->allowedFor($user, 'people.view')->isEmpty());
        // The control: a supervisor in the same development reads people.
        $reader = $this->createUser('supervisor_people');
        $this->assignRole($reader, 'supervisor', $dev);
        $this->assertSame([$dev], $this->access->allowedFor($reader, 'people.view')->ids());
    }

    public function testUnitsManageNeedsAWritableAssignment(): void
    {
        $dev = $this->createDevelopment('A');
        $user = $this->createUser('observer');
        $this->assignRole($user, 'administrator', $dev, -30, null, true);

        $this->assertTrue($this->access->allowedFor($user, 'units.manage')->isEmpty());
        // The control: the same read-only assignment still reads units through its other role permissions.
        $this->assertSame([$dev], $this->access->allowedFor($user, 'units.view')->ids());
    }

    public function testAResidentOnlyUserIsRefusedEveryUnitPermission(): void
    {
        $dev = $this->createDevelopment('A');
        $user = $this->createUser('tenant');
        $this->assignRole($user, 'resident', $dev);

        $this->assertTrue($this->access->allowedFor($user, 'units.view')->isEmpty());
        $this->assertTrue($this->access->allowedFor($user, 'units.manage')->isEmpty());
    }

    public function testAResidentWhoAlsoHoldsAStaffRoleKeepsTheStaffAccess(): void
    {
        $dev = $this->createDevelopment('A');
        $user = $this->createUser('tenant_clerk');
        $this->assignRole($user, 'resident', $dev);
        $this->assignRole($user, 'data_entry', $dev);

        $this->assertSame([$dev], $this->access->allowedFor($user, 'units.view')->ids());
    }

    public function testNoRoleHoldsDevelopmentsManageAndOnlyTheSuperAdministratorIsGrantedIt(): void
    {
        $dev = $this->createDevelopment('A');
        $root = $this->createUser('root');
        $this->assignRole($root, 'super_administrator', null);

        $this->assertTrue($this->access->allowedFor($root, 'developments.manage')->isAll());

        foreach (['administrator', 'accountant', 'data_entry', 'supervisor', 'guard', 'resident'] as $role) {
            $user = $this->createUser('u_' . $role);
            $this->assignRole($user, $role, $dev);
            $this->assertTrue($this->access->allowedFor($user, 'developments.manage')->isEmpty(), $role);
        }
    }

    /**
     * @return array<string, array{int, int|null, bool, string, bool}>
     */
    public static function superAssignmentsForManage(): array
    {
        // startsInDays, endsInDays, readOnly, user status, expected
        return [
            'open' => [-30, null, false, 'active', true],
            'ends today' => [-30, 0, false, 'active', true],
            'expired' => [-30, -1, false, 'active', false],
            'future' => [5, null, false, 'active', false],
            'read-only' => [-30, null, true, 'active', false],
            'disabled user' => [-30, null, false, 'disabled', false],
        ];
    }

    #[DataProvider('superAssignmentsForManage')]
    public function testDevelopmentsManageFollowsTheSuperAdministratorAssignment(
        int $startsInDays,
        ?int $endsInDays,
        bool $readOnly,
        string $status,
        bool $expected,
    ): void {
        $user = $this->createUser('root', null, $status);
        $this->assignRole($user, 'super_administrator', null, $startsInDays, $endsInDays, $readOnly);

        $this->assertSame($expected, $this->access->allowedFor($user, 'developments.manage')->isAll());
    }

    public function testSuperAdministratorHasNoExplicitRows(): void
    {
        $count = $this->db()->execute(
            "SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id = rp.role_id
             WHERE r.code = 'super_administrator'",
        )->fetch()[0];

        $this->assertSame(0, (int)$count);
    }

    public function testAssignmentEndingTodayGrantsInTheEveningInMexicoAndStopsAfterLocalMidnight(): void
    {
        $userId = $this->createUser('ann');
        $dev = $this->createDevelopment('A');
        $this->assignRoleOn($userId, 'administrator', $dev, '2026-11-01', '2026-11-17');

        // 2026-11-17 20:00 in Mexico City: the last day of the assignment, it still grants.
        $evening = new ActingAccessControl(null, new BusinessClock(null, new Chronos('2026-11-18T02:00:00Z', 'UTC')));
        $this->assertTrue($evening->allowedFor($userId, 'developments.view')->allows($dev));

        // 2026-11-18 00:30 in Mexico City: the assignment has ended.
        $night = new ActingAccessControl(null, new BusinessClock(null, new Chronos('2026-11-18T06:30:00Z', 'UTC')));
        $this->assertFalse($night->allowedFor($userId, 'developments.view')->allows($dev));
    }

    public function testAssignmentStartingOnTheBusinessDayDoesNotGrantBeforeLocalMidnight(): void
    {
        $userId = $this->createUser('ann');
        $dev = $this->createDevelopment('A');
        $this->assignRoleOn($userId, 'administrator', $dev, '2026-11-18');

        // 2026-11-17 20:00 in Mexico City: UTC is already on the 18th, the business day is not.
        $evening = new ActingAccessControl(null, new BusinessClock(null, new Chronos('2026-11-18T02:00:00Z', 'UTC')));
        $this->assertFalse($evening->allowedFor($userId, 'developments.view')->allows($dev));

        $night = new ActingAccessControl(null, new BusinessClock(null, new Chronos('2026-11-18T06:30:00Z', 'UTC')));
        $this->assertTrue($night->allowedFor($userId, 'developments.view')->allows($dev));
    }
}
