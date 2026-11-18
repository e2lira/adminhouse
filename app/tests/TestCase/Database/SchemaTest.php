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

    private function assign(int $user, string $roleCode, ?int $development): void
    {
        $this->db()->execute(
            'INSERT INTO role_assignments (user_id, role_id, development_id, starts_on, created, modified)
             VALUES (?, ?, ?, CURRENT_DATE, NOW(), NOW())',
            [$user, $this->roleId($roleCode), $development],
        );
    }
}
