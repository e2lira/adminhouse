<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Database\ExtensionGuard;
use App\Test\Support\DatabaseTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * AD12 (task 12.1): `btree_gist` is provisioned by `bin/provision_db_roles.php` (superuser) and checked, never
 * created, by the occupancies migration, which fails loudly when it is missing.
 */
class ExtensionGuardTest extends DatabaseTestCase
{
    /**
     * @return callable(string): (array<int, mixed>|false)
     */
    private function fetchRow(): callable
    {
        return fn(string $sql): array|false => $this->db()->execute($sql)->fetch();
    }

    public function testAnInstalledExtensionPasses(): void
    {
        ExtensionGuard::assertInstalled($this->fetchRow(), 'plpgsql');

        $this->addToAssertionCount(1);
    }

    public function testAMissingExtensionFailsWithAClearMessage(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The PostgreSQL extension "no_such_extension" is not installed');
        $this->expectExceptionMessage('php bin/provision_db_roles.php');

        ExtensionGuard::assertInstalled($this->fetchRow(), 'no_such_extension');
    }

    public function testAnUnsafeNameIsRefusedBeforeAnyQueryRuns(): void
    {
        $ran = false;
        $spy = function () use (&$ran): array|false {
            $ran = true;

            return false;
        };

        foreach (["x'; DROP TABLE users; --", 'Btree_Gist', '', 'a-b'] as $name) {
            try {
                ExtensionGuard::assertInstalled($spy, $name);
                $this->fail('An unsafe name must throw: ' . $name);
            } catch (InvalidArgumentException) {
                $this->assertFalse($ran, $name);
            }
        }
    }

    public function testTheTestDatabaseHasBtreeGist(): void
    {
        ExtensionGuard::assertInstalled($this->fetchRow(), 'btree_gist');

        $this->addToAssertionCount(1);
    }

    public function testTheProvisioningScriptCreatesBtreeGistAsSuperuser(): void
    {
        $script = (string)file_get_contents(dirname(__DIR__, 3) . '/bin/provision_db_roles.php');

        $this->assertStringContainsString('CREATE EXTENSION IF NOT EXISTS btree_gist', $script);
        // Created in the public schema, in every provisioned database, after the roles exist.
        $this->assertStringContainsString('WITH SCHEMA public', $script);
        $this->assertGreaterThan(
            (int)strpos($script, 'foreach ($databases as $database)'),
            (int)strpos($script, 'CREATE EXTENSION IF NOT EXISTS btree_gist'),
        );
    }

    public function testTheCiWorkflowProvisionsTheDatabaseBeforeTheTests(): void
    {
        $workflow = (string)file_get_contents(dirname(__DIR__, 4) . '/.github/workflows/app-ci.yml');

        $provision = (int)strpos($workflow, 'php bin/provision_db_roles.php --database=kualitet_app_test');
        $tests = (int)strpos($workflow, 'vendor/bin/phpunit');
        $this->assertGreaterThan(0, $provision);
        $this->assertGreaterThan($provision, $tests);
        // The script creates btree_gist, so the workflow needs no separate step: say so where a reader looks.
        $this->assertStringContainsString('btree_gist', $workflow);
    }
}
