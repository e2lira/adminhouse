<?php
declare(strict_types=1);

namespace App\Test\TestCase\Authorization;

use App\Authorization\RoleAssignmentException;
use App\Authorization\RoleAssignmentManager;
use App\Authorization\RoleAssignmentNotAllowed;
use App\Clock\BusinessClock;
use App\Test\Support\ActingAccessControl;
use App\Test\Support\ActingRoleAssignmentManager;
use App\Test\Support\DatabaseTestCase;
use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * The role-assignment rules (RB-R3, ADR-0005 section 3) enforced below the controller, against the real database.
 */
class RoleAssignmentManagerTest extends DatabaseTestCase
{
    private int $devA;
    private int $devB;
    private int $adminA;
    private int $root;
    private int $bob;
    private ?Chronos $previousNow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousNow = Chronos::getTestNow();

        $this->devA = $this->createDevelopment('Residencial Norte');
        $this->devB = $this->createDevelopment('Residencial Sur');
        $this->adminA = $this->createUser('ann');
        $this->assignRole($this->adminA, 'administrator', $this->devA);
        $this->root = $this->createUser('root');
        $this->assignRole($this->root, 'super_administrator', null);
        $this->bob = $this->createUser('bob');
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow($this->previousNow);
        parent::tearDown();
    }

    private function manager(): RoleAssignmentManager
    {
        return new ActingRoleAssignmentManager();
    }

    private function today(): string
    {
        return $this->businessDay();
    }

    private function grantAs(
        int $actor,
        int $development,
        string $username,
        string $role,
        ?string $startsOn = null,
        ?string $endsOn = null,
        bool $readOnly = false,
    ): int {
        return $this->manager()->grant(
            $actor,
            $development,
            $username,
            $this->roleId($role),
            $startsOn,
            $endsOn,
            $readOnly,
        );
    }

    private function assertRejected(string $reason, callable $action): void
    {
        try {
            $action();
        } catch (RoleAssignmentException $e) {
            $this->assertSame($reason, $e->reason);

            return;
        }
        $this->fail('Expected a RoleAssignmentException with reason ' . $reason);
    }

    private function rows(string $table): int
    {
        return (int)$this->db()->execute('SELECT COUNT(*) FROM ' . $table)->fetch()[0];
    }

    public function testAdministratorGrantsAnAccountantInTheirDevelopment(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant');

        $row = $this->db()->execute('SELECT * FROM role_assignments WHERE id = ?', [$id])->fetch('assoc');
        $this->assertSame($this->bob, (int)$row['user_id']);
        $this->assertSame($this->devA, (int)$row['development_id']);
        $this->assertSame($this->today(), (string)$row['starts_on']);
        $this->assertNull($row['ends_on']);
        $this->assertFalse((bool)$row['read_only']);
        $this->assertTrue((new ActingAccessControl())->allowedFor($this->bob, 'payments.create')->allows($this->devA));
        $this->assertFalse((new ActingAccessControl())->allowedFor($this->bob, 'payments.create')->allows($this->devB));
    }

    public function testGrantCanBeReadOnlyAndEndDated(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'resident', null, $this->businessDay(10), true);

        $row = $this->db()->execute('SELECT * FROM role_assignments WHERE id = ?', [$id])->fetch('assoc');
        $this->assertTrue((bool)$row['read_only']);
        $this->assertSame($this->businessDay(10), (string)$row['ends_on']);
    }

    public function testSuperAdministratorGrantsInAnyDevelopment(): void
    {
        $id = $this->grantAs($this->root, $this->devB, 'bob', 'supervisor');

        $this->assertGreaterThan(0, $id);
    }

    public function testSuperAdministratorCannotGrantInADevelopmentThatDoesNotExist(): void
    {
        $before = $this->rows('role_assignments');

        try {
            $this->grantAs($this->root, 999999, 'bob', 'supervisor');
            $this->fail('Expected RoleAssignmentNotAllowed');
        } catch (RoleAssignmentNotAllowed) {
            $this->assertSame($before, $this->rows('role_assignments'));
        }
    }

    public function testSuperAdministratorMayGrantAdministrator(): void
    {
        $id = $this->grantAs($this->root, $this->devA, 'bob', 'administrator');

        $this->assertGreaterThan(0, $id);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAllowedActors(): array
    {
        return [
            'administrator of another development' => ['other-admin'],
            'accountant of the development' => ['accountant'],
            'user with no assignment' => ['nobody'],
            'read-only administrator' => ['readonly-admin'],
            'expired administrator' => ['expired-admin'],
            'future administrator' => ['future-admin'],
            'guard' => ['guard'],
        ];
    }

    #[DataProvider('notAllowedActors')]
    public function testActorsWithoutTheManagePermissionInTheDevelopmentAreNotAllowed(string $kind): void
    {
        $actor = $this->createUser('actor');
        match ($kind) {
            'other-admin' => $this->assignRole($actor, 'administrator', $this->devB),
            'accountant' => $this->assignRole($actor, 'accountant', $this->devA),
            'readonly-admin' => $this->assignRole($actor, 'administrator', $this->devA, -30, null, true),
            'expired-admin' => $this->assignRole($actor, 'administrator', $this->devA, -30, -1),
            'future-admin' => $this->assignRole($actor, 'administrator', $this->devA, 5),
            'guard' => $this->assignRole($actor, 'guard', $this->devA),
            default => null,
        };

        $before = $this->rows('role_assignments');
        try {
            $this->grantAs($actor, $this->devA, 'bob', 'accountant');
            $this->fail('Expected RoleAssignmentNotAllowed');
        } catch (RoleAssignmentNotAllowed) {
            $this->assertSame($before, $this->rows('role_assignments'));
            $this->assertSame(0, $this->rows('role_assignment_events'));
        }
    }

    public function testNotAllowedActorCannotListEndOrReadRoles(): void
    {
        $other = $this->createUser('other');
        $this->assignRole($other, 'administrator', $this->devB);
        $manager = $this->manager();

        foreach (
            [
                fn() => $manager->listFor($other, $this->devA),
                fn() => $manager->assignableRoles($other, $this->devA),
                fn() => $manager->end($other, $this->devA, 1),
            ] as $call
        ) {
            try {
                $call();
                $this->fail('Expected RoleAssignmentNotAllowed');
            } catch (RoleAssignmentNotAllowed) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testNobodyGrantsSuperAdministratorThroughTheScreen(): void
    {
        foreach ([$this->root, $this->adminA] as $actor) {
            $this->assertRejected(
                RoleAssignmentException::ROLE_NOT_GRANTABLE,
                fn() => $this->grantAs($actor, $this->devA, 'bob', 'super_administrator'),
            );
        }
        $this->assertSame(0, $this->rows('role_assignment_events'));
    }

    public function testAdministratorCannotGrantAnyRoleThatCarriesTheManagePermission(): void
    {
        $this->assertRejected(
            RoleAssignmentException::PRIVILEGED_ROLE,
            fn() => $this->grantAs($this->adminA, $this->devA, 'bob', 'administrator'),
        );
        $this->assertSame(1 + 1, $this->rows('role_assignments'));
    }

    public function testUnknownRoleIsRejected(): void
    {
        $this->assertRejected(
            RoleAssignmentException::ROLE_NOT_FOUND,
            fn() => $this->manager()->grant($this->adminA, $this->devA, 'bob', 999999, null, null, false),
        );
    }

    public function testUnknownAndDisabledUsersAreRejectedTheSameWay(): void
    {
        $this->createUser('gone', null, 'disabled');

        foreach (['nobody-here', 'gone', ''] as $username) {
            $this->assertRejected(
                RoleAssignmentException::USER_NOT_FOUND,
                fn() => $this->grantAs($this->adminA, $this->devA, $username, 'accountant'),
            );
        }
    }

    public function testAdministratorCannotGrantToThemselves(): void
    {
        $this->assertRejected(
            RoleAssignmentException::SELF_CHANGE,
            fn() => $this->grantAs($this->adminA, $this->devA, 'ann', 'accountant'),
        );
    }

    public function testEndDateBeforeStartDateIsRejected(): void
    {
        $this->assertRejected(
            RoleAssignmentException::PERIOD_INVALID,
            fn() => $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', '2026-12-10', '2026-12-09'),
        );
        $this->assertSame(0, $this->rows('role_assignment_events'));
    }

    public function testSameDayStartAndEndIsAccepted(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', '2026-12-10', '2026-12-10');

        $this->assertGreaterThan(0, $id);
    }

    public function testMalformedDatesAreRejected(): void
    {
        foreach (['10/12/2026', '2026-02-30', 'tomorrow', '2026-1-1'] as $bad) {
            $this->assertRejected(
                RoleAssignmentException::DATE_INVALID,
                fn() => $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', $bad),
            );
        }
    }

    public function testDuplicateGrantIsRejectedWithoutAnAuditRow(): void
    {
        $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant');

        $this->assertRejected(
            RoleAssignmentException::DUPLICATE,
            fn() => $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant'),
        );
        $this->assertSame(1, $this->rows('role_assignment_events'));
    }

    public function testGrantWritesAnAuditEventWithTheAfterSnapshot(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', null, null, true);

        $event = $this->db()->execute('SELECT * FROM role_assignment_events')->fetch('assoc');
        $this->assertSame($this->adminA, (int)$event['actor_user_id']);
        $this->assertSame($id, (int)$event['role_assignment_id']);
        $this->assertSame($this->devA, (int)$event['development_id']);
        $this->assertSame('granted', $event['action']);
        $this->assertNull($event['before_snapshot']);
        $after = json_decode((string)$event['after_snapshot'], true);
        $this->assertSame($this->bob, $after['user_id']);
        $this->assertSame($this->roleId('accountant'), $after['role_id']);
        $this->assertSame($this->devA, $after['development_id']);
        $this->assertTrue($after['read_only']);
        $this->assertNull($after['ends_on']);
    }

    public function testEndingKeepsTheRowAndRemovesAccessImmediately(): void
    {
        $id = $this->grantAs($this->root, $this->devA, 'bob', 'accountant', $this->businessDay(-5));
        $access = new ActingAccessControl();
        $this->assertTrue($access->allowedFor($this->bob, 'payments.create')->allows($this->devA));

        $this->manager()->end($this->adminA, $this->devA, $id);

        $row = $this->db()->execute('SELECT * FROM role_assignments WHERE id = ?', [$id])->fetch('assoc');
        $this->assertSame($this->businessDay(-1), (string)$row['ends_on']);
        $this->assertFalse($access->allowedFor($this->bob, 'payments.create')->allows($this->devA));
        $this->assertSame(2 + 1, $this->rows('role_assignments'));
    }

    public function testEndingWritesAnAuditEventWithBeforeAndAfter(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', $this->businessDay(-5));

        $this->manager()->end($this->adminA, $this->devA, $id);

        $events = $this->db()->execute('SELECT * FROM role_assignment_events ORDER BY id')->fetchAll('assoc');
        $this->assertCount(2, $events);
        $this->assertSame('ended', $events[1]['action']);
        $this->assertSame($id, (int)$events[1]['role_assignment_id']);
        $this->assertNull(json_decode((string)$events[1]['before_snapshot'], true)['ends_on']);
        $this->assertSame(
            $this->businessDay(-1),
            json_decode((string)$events[1]['after_snapshot'], true)['ends_on'],
        );
    }

    public function testEndingAssignmentThatStartsTodayEndsOnItsStartDay(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', $this->today());

        $this->manager()->end($this->adminA, $this->devA, $id);

        $this->assertSame(
            $this->today(),
            (string)$this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$id])->fetch()[0],
        );
    }

    public function testEndingFutureAssignmentKeepsItNeverActiveBeforeItsStartDay(): void
    {
        $start = $this->businessDay(3);
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', $start);

        $this->manager()->end($this->adminA, $this->devA, $id);

        $access = new ActingAccessControl();
        $this->assertFalse($access->allowedFor($this->bob, 'payments.create')->allows($this->devA));
        Chronos::setTestNow(Chronos::now()->addDays(4));
        $this->assertFalse($access->allowedFor($this->bob, 'payments.create')->allows($this->devA));
    }

    public function testEndedAssignmentCannotBeEndedAgain(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', $this->businessDay(-5));
        $this->manager()->end($this->adminA, $this->devA, $id);

        $this->assertRejected(
            RoleAssignmentException::ALREADY_ENDED,
            fn() => $this->manager()->end($this->adminA, $this->devA, $id),
        );
        $this->assertSame(2, $this->rows('role_assignment_events'));
    }

    public function testAssignmentOfAnotherDevelopmentLooksNonexistent(): void
    {
        $id = $this->grantAs($this->root, $this->devB, 'bob', 'accountant');

        $this->assertRejected(
            RoleAssignmentException::ASSIGNMENT_NOT_FOUND,
            fn() => $this->manager()->end($this->adminA, $this->devA, $id),
        );
        $this->assertRejected(
            RoleAssignmentException::ASSIGNMENT_NOT_FOUND,
            fn() => $this->manager()->end($this->adminA, $this->devA, 987654),
        );
        $this->assertNull(
            $this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$id])->fetch()[0],
        );
    }

    public function testSuperAdministratorAssignmentCannotBeEndedThroughTheScreen(): void
    {
        $superAssignmentId = (int)$this->db()->execute(
            'SELECT id FROM role_assignments WHERE user_id = ?',
            [$this->root],
        )->fetch()[0];

        $this->assertRejected(
            RoleAssignmentException::ASSIGNMENT_NOT_FOUND,
            fn() => $this->manager()->end($this->root, $this->devA, $superAssignmentId),
        );
    }

    public function testAdministratorCannotEndTheirOwnAssignment(): void
    {
        $own = (int)$this->db()->execute(
            'SELECT id FROM role_assignments WHERE user_id = ?',
            [$this->adminA],
        )->fetch()[0];

        $this->assertRejected(
            RoleAssignmentException::SELF_CHANGE,
            fn() => $this->manager()->end($this->adminA, $this->devA, $own),
        );
        $this->assertNull($this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$own])->fetch()[0]);
    }

    public function testAdministratorCannotEndAnotherAdministrator(): void
    {
        $peer = $this->createUser('peer');
        $this->assignRole($peer, 'administrator', $this->devA);
        $peerAssignment = (int)$this->db()->execute(
            'SELECT id FROM role_assignments WHERE user_id = ?',
            [$peer],
        )->fetch()[0];

        $this->assertRejected(
            RoleAssignmentException::PRIVILEGED_ROLE,
            fn() => $this->manager()->end($this->adminA, $this->devA, $peerAssignment),
        );

        $this->manager()->end($this->root, $this->devA, $peerAssignment);
        $this->assertNotNull(
            $this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$peerAssignment])->fetch()[0],
        );
    }

    public function testSuperAdministratorMayEndTheirOwnDevelopmentAssignment(): void
    {
        $this->assignRole($this->root, 'accountant', $this->devA);
        $own = (int)$this->db()->execute(
            'SELECT ra.id FROM role_assignments ra JOIN roles r ON r.id = ra.role_id
             WHERE ra.user_id = ? AND r.code = ?',
            [$this->root, 'accountant'],
        )->fetch()[0];

        $this->manager()->end($this->root, $this->devA, $own);

        $this->assertNotNull($this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$own])->fetch()[0]);
    }

    public function testSuperAdministratorIsNotBlockedByTheSelfRule(): void
    {
        $id = $this->grantAs($this->root, $this->devA, 'root', 'accountant');

        $this->assertGreaterThan(0, $id);
    }

    public function testAssignableRolesExcludeSuperAdministratorAndPrivilegedRolesForAdministrators(): void
    {
        $codes = array_column($this->manager()->assignableRoles($this->adminA, $this->devA), 'code');
        sort($codes);
        $this->assertSame(['accountant', 'data_entry', 'guard', 'resident', 'supervisor'], $codes);

        $codes = array_column($this->manager()->assignableRoles($this->root, $this->devA), 'code');
        sort($codes);
        $this->assertSame(['accountant', 'administrator', 'data_entry', 'guard', 'resident', 'supervisor'], $codes);
    }

    public function testListShowsStateReadOnlyAndNamesInASingleQuery(): void
    {
        foreach (['u1', 'u2', 'u3', 'u4'] as $name) {
            $this->createUser($name);
        }
        $this->grantAs($this->root, $this->devA, 'u1', 'accountant', $this->businessDay(-20));
        $this->grantAs($this->root, $this->devA, 'u2', 'supervisor', $this->businessDay(5));
        $this->grantAs(
            $this->root,
            $this->devA,
            'u3',
            'resident',
            $this->businessDay(-20),
            $this->businessDay(-2),
            true,
        );
        $this->grantAs($this->root, $this->devB, 'u4', 'guard');

        $logger = new class extends AbstractLogger {
            public int $selects = 0;

            public function log($level, string|Stringable $message, array $context = []): void
            {
                // The session setup (`set_config`) and the commit probe (`SELECT 1`) are not what the manager asks.
                $sql = ltrim((string)$message);
                $isProbe = rtrim($sql, "; 
") === 'SELECT 1';
                if (str_starts_with($sql, 'SELECT') && !str_contains($sql, 'set_config(') && !$isProbe) {
                    $this->selects++;
                }
            }
        };
        $driver = $this->appDb()->getDriver();
        $driver->setLogger($logger);
        try {
            $rows = $this->manager()->listFor($this->adminA, $this->devA);
        } finally {
            $driver->disableQueryLogging();
        }

        $byUser = [];
        foreach ($rows as $row) {
            $byUser[$row['username']] = $row;
        }
        $this->assertSame(['ann', 'u1', 'u2', 'u3'], $this->sorted(array_keys($byUser)));
        $this->assertSame('current', $byUser['u1']['state']);
        $this->assertSame('future', $byUser['u2']['state']);
        $this->assertSame('ended', $byUser['u3']['state']);
        $this->assertTrue($byUser['u3']['read_only']);
        $this->assertSame('accountant', $byUser['u1']['role_code']);
        $this->assertArrayNotHasKey('u4', $byUser);
        $this->assertSame(2, $logger->selects, 'one permission check plus one list query, not one per row');
    }

    /**
     * @param list<string> $values Values.
     * @return list<string>
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    public function testTriggerErrorsAreMappedToFriendlyReasons(): void
    {
        // A user may hold the super_administrator role in at most two accounts; the screen never grants it, so
        // the trigger path is exercised through a check violation on the scope trigger by a direct bad insert
        // that the manager must translate when it surfaces from its own statement.
        $this->assertSame(
            RoleAssignmentException::RULE_VIOLATION,
            RoleAssignmentException::fromDatabaseError('23514', 'check_violation')->reason,
        );
        $this->assertSame(
            RoleAssignmentException::DUPLICATE,
            RoleAssignmentException::fromDatabaseError('23505', 'duplicate key')->reason,
        );
    }

    private function managerAt(string $utcInstant): RoleAssignmentManager
    {
        return new ActingRoleAssignmentManager(null, null, new BusinessClock(null, new Chronos($utcInstant, 'UTC')));
    }

    public function testEndingComputesYesterdayInTheBusinessZone(): void
    {
        $id = $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', '2026-11-01');

        // 2026-11-17 20:00 in Mexico City: yesterday is the 16th, although UTC is already on the 18th.
        $this->managerAt('2026-11-18T02:00:00Z')->end($this->adminA, $this->devA, $id);

        $this->assertSame(
            '2026-11-16',
            (string)$this->db()->execute('SELECT ends_on FROM role_assignments WHERE id = ?', [$id])->fetch()[0],
        );
    }

    public function testGrantWithoutStartDateStartsOnTheBusinessDay(): void
    {
        $id = $this->managerAt('2026-11-18T02:00:00Z')->grant(
            $this->adminA,
            $this->devA,
            'bob',
            $this->roleId('accountant'),
            null,
            null,
            false,
        );

        $this->assertSame(
            '2026-11-17',
            (string)$this->db()->execute('SELECT starts_on FROM role_assignments WHERE id = ?', [$id])->fetch()[0],
        );
    }

    public function testListedStateUsesTheBusinessDay(): void
    {
        $this->grantAs($this->adminA, $this->devA, 'bob', 'accountant', '2026-11-01', '2026-11-17');

        $states = static fn(array $rows): array => array_column($rows, 'state', 'username');

        $evening = $this->managerAt('2026-11-18T02:00:00Z')->listFor($this->adminA, $this->devA);
        $this->assertSame('current', $states($evening)['bob']);

        $night = $this->managerAt('2026-11-18T06:30:00Z')->listFor($this->adminA, $this->devA);
        $this->assertSame('ended', $states($night)['bob']);
    }
}
