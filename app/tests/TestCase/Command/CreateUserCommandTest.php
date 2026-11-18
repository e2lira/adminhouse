<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\Support\DatabaseTestCase;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;

class CreateUserCommandTest extends DatabaseTestCase
{
    use ConsoleIntegrationTestTrait;

    private const PASSWORD = 'a long passphrase for tests';

    public function testCreatesUserAndHashedCredentials(): void
    {
        $this->exec('create_user alice', [self::PASSWORD, self::PASSWORD]);

        $this->assertExitSuccess();
        $user = $this->db()->execute("SELECT id, status, person_id FROM users WHERE username = 'alice'")->fetch('assoc');
        $this->assertNotFalse($user);
        $this->assertSame('active', $user['status']);
        $this->assertNull($user['person_id']);

        $hash = $this->db()->execute('SELECT password_hash FROM user_credentials WHERE user_id = ?', [$user['id']])
            ->fetch()[0];
        $this->assertNotSame(self::PASSWORD, $hash);
        $this->assertStringNotContainsString(self::PASSWORD, $hash);
        $this->assertTrue((new DefaultPasswordHasher())->check(self::PASSWORD, $hash));
    }

    public function testDoesNotAssignRolesByDefault(): void
    {
        $this->exec('create_user alice', [self::PASSWORD, self::PASSWORD]);

        $count = $this->db()->execute('SELECT COUNT(*) FROM role_assignments')->fetch()[0];
        $this->assertSame(0, (int)$count);
    }

    public function testCanAssignSuperAdministrator(): void
    {
        $this->exec('create_user root --super-administrator', [self::PASSWORD, self::PASSWORD]);

        $this->assertExitSuccess();
        $row = $this->db()->execute(
            "SELECT r.code, ra.development_id, ra.read_only
             FROM role_assignments ra
             JOIN roles r ON r.id = ra.role_id
             JOIN users u ON u.id = ra.user_id
             WHERE u.username = 'root'",
        )->fetch('assoc');
        $this->assertSame('super_administrator', $row['code']);
        $this->assertNull($row['development_id']);
        $this->assertFalse((bool)$row['read_only']);
    }

    public function testRejectsAMismatchedConfirmation(): void
    {
        $this->exec('create_user alice', [self::PASSWORD, 'something else entirely']);

        $this->assertExitError();
        $this->assertErrorContains('do not match');
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0]);
    }

    public function testRejectsAShortPassword(): void
    {
        $this->exec('create_user alice', ['short', 'short']);

        $this->assertExitError();
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0]);
    }

    public function testRejectsADuplicateUsername(): void
    {
        $this->createUser('alice', null);

        $this->exec('create_user alice', [self::PASSWORD, self::PASSWORD]);

        $this->assertExitError();
        $this->assertErrorContains('already exists');
    }

    public function testPasswordIsNotAcceptedAsAnArgument(): void
    {
        $this->exec('create_user alice --password secret');

        $this->assertExitError();
        $this->assertSame(0, (int)$this->db()->execute('SELECT COUNT(*) FROM users')->fetch()[0]);
    }

    public function testDoesNotPrintThePassword(): void
    {
        $this->exec('create_user alice', [self::PASSWORD, self::PASSWORD]);

        $this->assertOutputNotContains(self::PASSWORD);
        $this->assertStringNotContainsString(self::PASSWORD, implode("
", $this->_err->messages()));
    }
}
