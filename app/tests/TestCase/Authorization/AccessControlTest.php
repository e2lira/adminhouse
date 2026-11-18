<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authorization;

use App\Authorization\AccessControl;
use App\Test\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Which developments a user may act on for a permission (RB-R3, RB-R4, ADR-0005).
 */
class AccessControlTest extends DatabaseTestCase
{
    private AccessControl $access;

    protected function setUp(): void
    {
        parent::setUp();
        $this->access = new AccessControl();
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
                'developments.view', 'payments.create', 'reports.view', 'role_assignments.manage', 'units.view',
            ]],
            'accountant' => ['accountant', ['developments.view', 'payments.create', 'reports.view', 'units.view']],
            'data_entry' => ['data_entry', ['developments.view', 'payments.create', 'units.view']],
            'supervisor' => ['supervisor', ['developments.view', 'reports.view', 'units.view']],
            'guard' => ['guard', ['gate.log']],
            'resident' => ['resident', ['units.view']],
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

    public function testSuperAdministratorHasNoExplicitRows(): void
    {
        $count = $this->db()->execute(
            "SELECT COUNT(*) FROM role_permissions rp JOIN roles r ON r.id = rp.role_id
             WHERE r.code = 'super_administrator'",
        )->fetch()[0];

        $this->assertSame(0, (int)$count);
    }
}
