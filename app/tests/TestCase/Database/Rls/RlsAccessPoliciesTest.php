<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

/**
 * Policies of `role_assignments`, the RBAC catalogues, the secret tables and the two audit trails.
 */
class RlsAccessPoliciesTest extends RlsTestCase
{
    private const INSERT_ASSIGNMENT = "INSERT INTO role_assignments
        (user_id, role_id, development_id, starts_on, read_only, created, modified)
        VALUES (?, (SELECT id FROM roles WHERE code = ?), ?, CURRENT_DATE - 1, false, NOW(), NOW())";

    private function assignmentId(int $userId): int
    {
        return (int)$this->db()->execute('SELECT id FROM role_assignments WHERE user_id = ? LIMIT 1', [$userId])->fetch()[0];
    }

    // role_assignments --------------------------------------------------------------------------------------------

    public function testNoSessionSeesNoAssignment(): void
    {
        $this->actAs(null);

        $this->assertSame([], $this->visibleIds('role_assignments'));
    }

    public function testAUserSeesOnlyTheirOwnAssignments(): void
    {
        foreach (['accountantA', 'resident', 'guardA'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([$this->assignmentId($this->{$user})], $this->visibleIds('role_assignments'), $user);
            $this->endSession();
        }
    }

    public function testAnAdministratorSeesTheAssignmentsOfTheirDevelopmentOnly(): void
    {
        $this->actAs($this->adminA);

        $developments = array_unique($this->ids('SELECT development_id FROM role_assignments'));
        $this->assertSame([$this->devA], array_values($developments));
        $this->assertCount(8, $this->visibleIds('role_assignments'));
    }

    public function testSuperAdministratorSeesEveryAssignment(): void
    {
        $this->actAs($this->superUser);

        $this->assertCount(11 - 1, $this->visibleIds('role_assignments'));
    }

    public function testDisabledEndedAndFutureAdministratorsAdministerNothing(): void
    {
        foreach (['disabledAdmin', 'endedAdmin', 'futureAdmin'] as $user) {
            $this->actAs($this->{$user});
            $visible = $this->visibleIds('role_assignments');
            $this->assertSame($user === 'disabledAdmin' ? [] : [$this->assignmentId($this->{$user})], $visible, $user);
            $this->endSession();
        }
    }

    public function testJobContextSeesNoAssignment(): void
    {
        $this->actAsJob([$this->devA]);

        $this->assertSame([], $this->visibleIds('role_assignments'));
    }

    public function testAnAdministratorGrantsRolesInTheirDevelopmentOnly(): void
    {
        $this->actAs($this->adminA);

        $this->assertAllowed(self::INSERT_ASSIGNMENT, [$this->loner, 'accountant', $this->devA]);
        $this->assertRejected(self::INSERT_ASSIGNMENT, [$this->loner, 'accountant', $this->devB]);
    }

    public function testNobodyElseGrantsRoles(): void
    {
        foreach (['accountantA', 'guardA', 'resident', 'disabledAdmin', 'endedAdmin', 'futureAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertRejected(self::INSERT_ASSIGNMENT, [$this->loner, 'resident', $this->devA]);
            $this->endSession();
        }
        $this->actAs(null);
        $this->assertRejected(self::INSERT_ASSIGNMENT, [$this->loner, 'resident', $this->devA]);
        $this->endSession();
        $this->actAsJob([$this->devA]);
        $this->assertRejected(self::INSERT_ASSIGNMENT, [$this->loner, 'resident', $this->devA]);
    }

    public function testSuperAdministratorGrantsRolesAnywhere(): void
    {
        $this->actAs($this->superUser);

        $this->assertAllowed(self::INSERT_ASSIGNMENT, [$this->loner, 'administrator', $this->devA]);
        $this->assertAllowed(self::INSERT_ASSIGNMENT, [$this->loner, 'administrator', $this->devB]);
    }

    public function testAnAdministratorCannotGrantTheSuperAdministratorRole(): void
    {
        $this->actAs($this->adminA);

        $this->assertRejected(self::INSERT_ASSIGNMENT, [$this->loner, 'super_administrator', null]);
        $this->assertRejected(self::INSERT_ASSIGNMENT, [$this->loner, 'super_administrator', $this->devA]);
    }

    public function testSuperAdministratorGrantsTheGlobalRoleUpToTheLimit(): void
    {
        $this->actAs($this->superUser);
        $this->assertAllowed(self::INSERT_ASSIGNMENT, [$this->loner, 'super_administrator', null]);
        // The third account is refused by the two-account limit trigger.
        $this->assertRejected(self::INSERT_ASSIGNMENT, [$this->adminA, 'super_administrator', null]);
    }

    public function testAnAdministratorEndsAssignmentsInTheirDevelopmentOnly(): void
    {
        $adminBAssignment = $this->assignmentId($this->adminB);
        $this->actAs($this->adminA);

        $this->assertAllowed(
            'UPDATE role_assignments SET ends_on = CURRENT_DATE, modified = NOW() WHERE user_id = ?',
            [$this->accountantA],
        );
        $this->assertDenied('UPDATE role_assignments SET ends_on = CURRENT_DATE WHERE id = ?', [$adminBAssignment]);
    }

    public function testNobodyElseUpdatesAssignments(): void
    {
        foreach (['accountantA', 'resident', 'disabledAdmin', 'endedAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertDenied('UPDATE role_assignments SET ends_on = CURRENT_DATE WHERE user_id IN (?, ?)', [
                $this->{$user},
                $this->accountantA,
            ]);
            $this->endSession();
        }
        $this->assertNull($this->db()->execute('SELECT ends_on FROM role_assignments WHERE user_id = ?', [$this->accountantA])->fetch()[0]);
    }

    public function testTheKeyOfAnAssignmentCannotChange(): void
    {
        foreach ([$this->adminA, $this->superUser] as $actor) {
            $this->actAs($actor);
            $this->assertRejected('UPDATE role_assignments SET user_id = ? WHERE user_id = ?', [$this->loner, $this->accountantA]);
            $this->assertRejected(
                "UPDATE role_assignments SET role_id = (SELECT id FROM roles WHERE code = 'resident') WHERE user_id = ?",
                [$this->accountantA],
            );
            $this->assertRejected('UPDATE role_assignments SET development_id = ? WHERE user_id = ?', [$this->devB, $this->accountantA]);
            $this->endSession();
        }
        // The owner cannot either: revoke and create instead (ADR-0005).
        try {
            $this->db()->execute('UPDATE role_assignments SET development_id = ? WHERE user_id = ?', [$this->devB, $this->accountantA]);
            $this->fail('The trigger must reject a key change.');
        } catch (Throwable $e) {
            $this->assertStringContainsString('cannot be changed', $e->getMessage());
        }
    }

    public function testAnAdministratorDeletesAssignmentsInTheirDevelopmentOnly(): void
    {
        $this->actAs($this->adminA);

        $this->assertAllowed('DELETE FROM role_assignments WHERE user_id = ?', [$this->guardA]);
        $this->assertDenied('DELETE FROM role_assignments WHERE user_id = ?', [$this->adminB]);
        $this->endSession();

        $this->actAs($this->accountantA);
        $this->assertDenied('DELETE FROM role_assignments WHERE user_id = ?', [$this->resident]);
    }

    // roles, permissions, role_permissions ------------------------------------------------------------------------

    public function testCataloguesAreReadableByEveryone(): void
    {
        foreach ([null, $this->loner, $this->resident, $this->superUser] as $user) {
            $this->actAs($user);
            foreach (['roles', 'permissions', 'role_permissions'] as $table) {
                $this->assertNotSame([], $this->visibleIds($table), (string)$table);
            }
            $this->endSession();
        }
        $this->actAsJob([]);
        $this->assertCount(7, $this->visibleIds('roles'));
    }

    public function testCataloguesAreWritableByTheMigrationRoleOnly(): void
    {
        foreach ([$this->superUser, $this->adminA, null] as $user) {
            $this->actAs($user);
            $this->assertRejected("INSERT INTO roles (code, name) VALUES ('rogue', 'Rogue')");
            $this->assertRejected("UPDATE roles SET name = 'x' WHERE code = 'guard'");
            $this->assertRejected("DELETE FROM roles WHERE code = 'guard'");
            $this->assertRejected("INSERT INTO permissions (code, description) VALUES ('x.y', 'z')");
            $this->assertRejected("UPDATE permissions SET description = 'x'");
            $this->assertRejected('DELETE FROM role_permissions');
            $this->assertRejected(
                "INSERT INTO role_permissions (role_id, permission_id)
                 SELECT r.id, p.id FROM roles r, permissions p WHERE r.code = 'guard' AND p.code = 'reports.view'",
            );
            $this->endSession();
        }
        $this->assertSame(7, (int)$this->db()->execute('SELECT COUNT(*) FROM roles')->fetch()[0]);
    }

    // credentials, TOTP and backup codes --------------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function secretTables(): array
    {
        return [
            'user_credentials' => ['user_credentials'],
            'user_totp' => ['user_totp'],
            'user_backup_codes' => ['user_backup_codes'],
        ];
    }

    /**
     * @param string $table Table.
     * @return void
     */
    #[DataProvider('secretTables')]
    public function testSecretTablesAreClosedToTheRuntimeRole(string $table): void
    {
        $this->db()->execute(
            'INSERT INTO user_credentials (user_id, password_hash, password_changed_at) VALUES (?, ?, NOW())',
            [$this->loner, 'hash'],
        );
        $this->db()->execute(
            "INSERT INTO user_totp (user_id, secret_encrypted, confirmed_at, created) VALUES (?, 's', NOW(), NOW())",
            [$this->loner],
        );
        $this->db()->execute(
            "INSERT INTO user_backup_codes (user_id, code_hash, created) VALUES (?, 'c', NOW())",
            [$this->loner],
        );

        foreach ([null, $this->loner, $this->adminA, $this->superUser] as $user) {
            $this->actAs($user);
            $this->assertRejected("SELECT * FROM {$table}");
            $this->assertRejected("SELECT count(*) FROM {$table}");
            $this->assertRejected("DELETE FROM {$table}");
            $this->assertRejected("UPDATE {$table} SET user_id = user_id");
            $this->endSession();
        }
        $this->actAsJob([$this->devA]);
        $this->assertRejected("SELECT * FROM {$table}");
    }

    public function testSecretTablesRejectInsertsFromTheRuntimeRole(): void
    {
        $this->actAs($this->superUser);

        $this->assertRejected(
            'INSERT INTO user_credentials (user_id, password_hash, password_changed_at) VALUES (?, ?, NOW())',
            [$this->loner, 'attacker-hash'],
        );
        $this->assertRejected(
            "INSERT INTO user_totp (user_id, secret_encrypted, created) VALUES (?, 'x', NOW())",
            [$this->loner],
        );
        $this->assertRejected(
            "INSERT INTO user_backup_codes (user_id, code_hash, created) VALUES (?, 'x', NOW())",
            [$this->loner],
        );
    }

    // auth_events -------------------------------------------------------------------------------------------------

    private function createAuthEvent(?int $userId): int
    {
        return (int)$this->db()->execute(
            "INSERT INTO auth_events (user_id, username, event_type, created) VALUES (?, 'x', 'password_ok', NOW()) RETURNING id",
            [$userId],
        )->fetch()[0];
    }

    public function testAuthEventsAreReadableByTheirUserAndTheSuperAdministratorOnly(): void
    {
        $adminEvent = $this->createAuthEvent($this->adminA);
        $residentEvent = $this->createAuthEvent($this->resident);
        $anonymousEvent = $this->createAuthEvent(null);

        $this->actAs(null);
        $this->assertSame([], $this->visibleIds('auth_events'));
        $this->endSession();

        $this->actAs($this->adminA);
        $this->assertSame([$adminEvent], $this->visibleIds('auth_events'));
        $this->endSession();

        $this->actAs($this->resident);
        $this->assertSame([$residentEvent], $this->visibleIds('auth_events'));
        $this->endSession();

        $this->actAs($this->superUser);
        $this->assertSame([$adminEvent, $residentEvent, $anonymousEvent], $this->visibleIds('auth_events'));
        $this->endSession();

        $this->actAsJob([$this->devA]);
        $this->assertSame([], $this->visibleIds('auth_events'));
    }

    public function testAuthEventsAreAppendOnlyForTheRuntimeRole(): void
    {
        $id = $this->createAuthEvent($this->superUser);
        $this->actAs($this->superUser);

        $this->assertRejected('UPDATE auth_events SET event_type = ? WHERE id = ?', ['password_failed', $id]);
        $this->assertRejected('DELETE FROM auth_events WHERE id = ?', [$id]);
        $this->assertRejected('TRUNCATE auth_events');
        $this->assertRejected(
            "INSERT INTO auth_events (user_id, username, event_type, created) VALUES (1, 'x', 'password_ok', NOW())",
        );
    }

    // role_assignment_events --------------------------------------------------------------------------------------

    private const INSERT_EVENT = "INSERT INTO role_assignment_events
        (actor_user_id, role_assignment_id, development_id, action, after_snapshot, created)
        VALUES (?, ?, ?, 'granted', '{}'::jsonb, NOW())";

    private function assignmentIdOf(int $userId): int
    {
        return (int)$this->db()->execute('SELECT id FROM role_assignments WHERE user_id = ?', [$userId])->fetch()[0];
    }

    public function testAssignmentEventsAreReadableByTheAdministratorsOfThatDevelopment(): void
    {
        $eventA = $this->createAssignmentEvent($this->adminA, $this->devA);
        $eventB = $this->createAssignmentEvent($this->adminB, $this->devB);

        $this->actAs(null);
        $this->assertSame([], $this->visibleIds('role_assignment_events'));
        $this->endSession();

        $this->actAs($this->adminA);
        $this->assertSame([$eventA], $this->visibleIds('role_assignment_events'));
        $this->endSession();

        $this->actAs($this->adminB);
        $this->assertSame([$eventB], $this->visibleIds('role_assignment_events'));
        $this->endSession();

        $this->actAs($this->superUser);
        $this->assertSame([$eventA, $eventB], $this->visibleIds('role_assignment_events'));
        $this->endSession();

        foreach (['accountantA', 'resident', 'guardA', 'disabledAdmin', 'endedAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertSame([], $this->visibleIds('role_assignment_events'), $user);
            $this->endSession();
        }
        $this->actAsJob([$this->devA]);
        $this->assertSame([], $this->visibleIds('role_assignment_events'));
    }

    public function testAnAdministratorRecordsEventsAsThemselvesForTheirDevelopment(): void
    {
        $inA = $this->assignmentIdOf($this->resident);
        $this->actAs($this->adminA);

        $this->assertAllowed(self::INSERT_EVENT, [$this->adminA, $inA, $this->devA]);
        $this->assertRejected(self::INSERT_EVENT, [$this->adminA, $inA, $this->devB]);
        // Forging the actor is refused.
        $this->assertRejected(self::INSERT_EVENT, [$this->adminB, $inA, $this->devA]);
    }

    public function testAnEventMustPointToAnAssignmentOfTheSameDevelopment(): void
    {
        $inB = $this->assignmentIdOf($this->adminB);

        // The administrator of A cannot write history about an assignment of B, nor one that does not exist,
        // whichever development the row claims.
        $this->actAs($this->adminA);
        $this->assertRejected(self::INSERT_EVENT, [$this->adminA, $inB, $this->devA]);
        $this->assertRejected(self::INSERT_EVENT, [$this->adminA, $inB, $this->devB]);
        $this->assertRejected(self::INSERT_EVENT, [$this->adminA, 999999, $this->devA]);
        $this->endSession();

        // Not even the super-administrator can pair an assignment with a different development.
        $this->actAs($this->superUser);
        $this->assertRejected(self::INSERT_EVENT, [$this->superUser, $inB, $this->devA]);
        $this->assertAllowed(self::INSERT_EVENT, [$this->superUser, $inB, $this->devB]);
    }

    public function testOthersCannotRecordEvents(): void
    {
        $inA = $this->assignmentIdOf($this->resident);
        foreach (['accountantA', 'resident', 'disabledAdmin', 'endedAdmin', 'loner'] as $user) {
            $this->actAs($this->{$user});
            $this->assertRejected(self::INSERT_EVENT, [$this->{$user}, $inA, $this->devA]);
            $this->endSession();
        }
        $this->actAs(null);
        $this->assertRejected(self::INSERT_EVENT, [1, $inA, $this->devA]);
        $this->endSession();
        $this->actAsJob([$this->devA]);
        $this->assertRejected(self::INSERT_EVENT, [1, $inA, $this->devA]);
    }

    public function testAssignmentEventsAreAppendOnlyForTheRuntimeRole(): void
    {
        $id = $this->createAssignmentEvent($this->adminA, $this->devA);
        $this->actAs($this->superUser);

        $this->assertRejected("UPDATE role_assignment_events SET action = 'ended' WHERE id = ?", [$id]);
        $this->assertRejected('DELETE FROM role_assignment_events WHERE id = ?', [$id]);
        $this->assertRejected('TRUNCATE role_assignment_events');
    }
}
