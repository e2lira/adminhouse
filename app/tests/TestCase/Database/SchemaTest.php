<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Test\Support\DatabaseTestCase;
use PDOException;

/**
 * Database-level guarantees of the identity and access schema.
 */
class SchemaTest extends DatabaseTestCase
{
    public function testRoleCatalogueIsSeeded(): void
    {
        $rows = $this->db()->execute('SELECT code FROM roles ORDER BY code')->fetchAll('assoc');

        $this->assertSame([
            'accountant',
            'administrator',
            'data_entry',
            'guard',
            'resident',
            'super_administrator',
            'supervisor',
        ], array_column($rows, 'code'));
    }

    public function testRoleDisplayNamesAreInSpanish(): void
    {
        $name = $this->db()->execute("SELECT name FROM roles WHERE code = 'administrator'")->fetch()[0];

        $this->assertSame('Administrador', $name);
    }

    public function testRoleCodeCannotBeChanged(): void
    {
        $this->expectException(PDOException::class);
        $this->db()->execute("UPDATE roles SET code = 'owner' WHERE code = 'guard'");
    }

    public function testUsernameIsUnique(): void
    {
        $this->createUser('alice', null);

        $this->expectException(PDOException::class);
        $this->createUser('alice', null);
    }

    public function testUserStatusIsRestricted(): void
    {
        $this->expectException(PDOException::class);
        $this->createUser('bob', null, 'banned');
    }

    public function testCredentialsAreKeptOutOfUsers(): void
    {
        $schema = $this->db()->getSchemaCollection();

        $this->assertNotContains('password_hash', $schema->describe('users')->columns());
        $this->assertNotContains('password', $schema->describe('users')->columns());
        $this->assertContains('password_hash', $schema->describe('user_credentials')->columns());
    }

    public function testDevelopmentTypeIsRestricted(): void
    {
        $this->expectException(PDOException::class);
        $this->db()->execute(
            "INSERT INTO developments (type, name, section_label, number_label, created, modified)
             VALUES ('castle', 'X', 'a', 'b', NOW(), NOW())",
        );
    }

    public function testPersonDevelopmentPairIsUnique(): void
    {
        $dev = $this->createDevelopment();
        $this->db()->execute("INSERT INTO people (name, created, modified) VALUES ('Persona Uno', NOW(), NOW())");
        $person = (int)$this->db()->execute('SELECT id FROM people')->fetch()[0];
        $this->db()->execute(
            'INSERT INTO person_developments (person_id, development_id) VALUES (?, ?)',
            [$person, $dev],
        );

        $this->expectException(PDOException::class);
        $this->db()->execute(
            'INSERT INTO person_developments (person_id, development_id) VALUES (?, ?)',
            [$person, $dev],
        );
    }

    public function testAssignmentPerDevelopmentIsUnique(): void
    {
        $user = $this->createUser('carol', null);
        $dev = $this->createDevelopment();
        $this->assign($user, 'accountant', $dev);

        $this->expectException(PDOException::class);
        $this->assign($user, 'accountant', $dev);
    }

    public function testSameRoleInAnotherDevelopmentIsAllowed(): void
    {
        $user = $this->createUser('dave', null);
        $this->assign($user, 'accountant', $this->createDevelopment('A'));
        $this->assign($user, 'accountant', $this->createDevelopment('B'));

        $count = $this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0];
        $this->assertSame(2, (int)$count);
    }

    public function testSuperAdministratorAssignmentIsUniquePerUserWithNullDevelopment(): void
    {
        $user = $this->createUser('erin', null);
        $this->assign($user, 'super_administrator', null);

        $this->expectException(PDOException::class);
        $this->assign($user, 'super_administrator', null);
    }

    public function testSuperAdministratorCannotBeScopedToADevelopment(): void
    {
        $user = $this->createUser('frank', null);

        $this->expectException(PDOException::class);
        $this->assign($user, 'super_administrator', $this->createDevelopment());
    }

    public function testOtherRolesRequireADevelopment(): void
    {
        $user = $this->createUser('grace', null);

        $this->expectException(PDOException::class);
        $this->assign($user, 'administrator', null);
    }

    public function testChangingAnAssignmentCannotBreakTheRule(): void
    {
        $user = $this->createUser('heidi', null);
        $this->assign($user, 'administrator', $this->createDevelopment());

        $this->expectException(PDOException::class);
        $this->db()->execute(
            'UPDATE role_assignments SET development_id = NULL WHERE user_id = ?',
            [$user],
        );
    }

    public function testAssignmentEndCannotPrecedeItsStart(): void
    {
        $user = $this->createUser('ivan', null);

        $this->expectException(PDOException::class);
        $this->db()->execute(
            "INSERT INTO role_assignments (user_id, role_id, development_id, starts_on, ends_on, created, modified)
             VALUES (?, ?, ?, '2026-02-01', '2026-01-01', NOW(), NOW())",
            [$user, $this->roleId('resident'), $this->createDevelopment()],
        );
    }

    public function testDeletingAUserRemovesCredentialsAndAssignments(): void
    {
        $user = $this->createUser('judy');
        $this->assign($user, 'guard', $this->createDevelopment());

        $this->db()->execute('DELETE FROM users WHERE id = ?', [$user]);

        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM user_credentials')->fetch()[0]);
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0]);
    }

    public function testSecondSuperAdministratorIsAllowed(): void
    {
        $this->assign($this->createUser('one', null), 'super_administrator', null);
        $this->assign($this->createUser('two', null), 'super_administrator', null);

        $count = $this->db()->execute(
            'SELECT COUNT(DISTINCT user_id) FROM role_assignments WHERE role_id = ?',
            [$this->roleId('super_administrator')],
        )->fetch()[0];
        $this->assertSame(2, (int)$count);
    }

    public function testThirdSuperAdministratorIsRefusedByTheDatabase(): void
    {
        $this->assign($this->createUser('one', null), 'super_administrator', null);
        $this->assign($this->createUser('two', null), 'super_administrator', null);
        $third = $this->createUser('three', null);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('At most 2 accounts');
        $this->assign($third, 'super_administrator', null);
    }

    public function testEndedSuperAdministratorAssignmentStillCountsTowardsTheLimit(): void
    {
        $this->assignRole($this->createUser('one', null), 'super_administrator', null, -30, -1);
        $this->assign($this->createUser('two', null), 'super_administrator', null);
        $third = $this->createUser('three', null);

        $this->expectException(PDOException::class);
        $this->assign($third, 'super_administrator', null);
    }

    public function testOtherRolesAreNotLimited(): void
    {
        $dev = $this->createDevelopment();
        foreach (['a', 'b', 'c'] as $name) {
            $this->assign($this->createUser($name, null), 'administrator', $dev);
        }

        $this->assertSame(3, (int)$this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0]);
    }

    public function testAuthEventsAreAppendOnly(): void
    {
        $this->db()->execute("INSERT INTO auth_events (event_type, created) VALUES ('password_ok', NOW())");

        try {
            $this->db()->execute("UPDATE auth_events SET event_type = 'totp_ok'");
            $this->fail('UPDATE on auth_events must be rejected');
        } catch (PDOException $e) {
            $this->assertStringContainsString('append-only', $e->getMessage());
        }

        $this->expectException(PDOException::class);
        $this->db()->execute('DELETE FROM auth_events');
    }

    public function testAuthEventTypeIsRestricted(): void
    {
        $this->expectException(PDOException::class);
        $this->db()->execute("INSERT INTO auth_events (event_type, created) VALUES ('made_up', NOW())");
    }

    public function testTotpMaterialLivesOutsideUsersAndCredentials(): void
    {
        $schema = $this->db()->getSchemaCollection();

        $this->assertSame([], array_values(array_intersect(
            ['totp_secret', 'totp_enabled', 'backup_codes'],
            $schema->describe('users')->columns(),
        )));
        $this->assertContains('secret_encrypted', $schema->describe('user_totp')->columns());
        $this->assertContains('code_hash', $schema->describe('user_backup_codes')->columns());
    }

    public function testDeletingAUserRemovesItsTotpAndBackupCodesButKeepsAuditEvents(): void
    {
        $user = $this->createUser('kim');
        $this->db()->execute(
            'INSERT INTO user_totp (user_id, secret_encrypted, created) VALUES (?, ?, NOW())',
            [$user, 'x'],
        );
        $this->db()->execute(
            'INSERT INTO user_backup_codes (user_id, code_hash, created) VALUES (?, ?, NOW())',
            [$user, 'h'],
        );
        $this->db()->execute(
            "INSERT INTO auth_events (user_id, event_type, created) VALUES (?, 'password_ok', NOW())",
            [$user],
        );

        $this->db()->execute('DELETE FROM users WHERE id = ?', [$user]);

        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM user_totp')->fetch()[0]);
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM user_backup_codes')->fetch()[0]);
        $this->assertSame(1, (int)$this->db()->execute('SELECT COUNT(*) FROM auth_events')->fetch()[0]);
    }

    private function assign(int $user, string $roleCode, ?int $development): void
    {
        $this->db()->execute(
            'INSERT INTO role_assignments (user_id, role_id, development_id, starts_on, created, modified)
             VALUES (?, ?, ?, CURRENT_DATE, NOW(), NOW())',
            [$user, $this->roleId($roleCode), $development],
        );
    }
}
