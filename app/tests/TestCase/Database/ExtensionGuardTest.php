<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Database\ExtensionGuard;
use App\Test\Support\DatabaseTestCase;
use CreateOccupancies;
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

    public function testEveryEmptyAnswerCountsAsMissing(): void
    {
        foreach ([false, null, []] as $answer) {
            try {
                ExtensionGuard::assertInstalled(fn(string $sql): mixed => $answer, 'btree_gist');
                $this->fail('An empty answer must throw: ' . json_encode($answer));
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('"btree_gist"', $e->getMessage());
            }
        }
        // Any real row passes.
        ExtensionGuard::assertInstalled(fn(string $sql): array => [1], 'btree_gist');
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

        foreach (
            ["x'; DROP TABLE users; --", 'Btree_Gist', '', 'a-b', "btree_gist
", ' btree_gist'] as $name
        ) {
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

    /**
     * @param string $source Source text.
     * @param string $needle Text that opens a brace block, for example `foreach (...) {`.
     * @return string Body between the braces of the first block that starts at `$needle`.
     */
    private function blockAfter(string $source, string $needle): string
    {
        $start = strpos($source, $needle);
        $this->assertNotFalse($start, 'Not found: ' . $needle);
        $open = strpos($source, '{', $start);
        $this->assertNotFalse($open);
        $depth = 0;
        for ($i = $open, $length = strlen($source); $i < $length; $i++) {
            $depth += match ($source[$i]) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };
            if ($depth === 0) {
                return substr($source, $open + 1, $i - $open - 1);
            }
        }
        $this->fail('Unbalanced braces after ' . $needle);
    }

    public function testTheProvisioningScriptCreatesBtreeGistInEveryProvisionedDatabase(): void
    {
        $script = (string)file_get_contents(dirname(__DIR__, 3) . '/bin/provision_db_roles.php');
        $statement = 'CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA public';

        $this->assertSame(1, substr_count($script, 'CREATE EXTENSION'), 'one extension statement in the script');
        // Inside the per-database loop (not once for the first database), executed on that database's connection.
        $loop = $this->blockAfter($script, 'foreach ($databases as $database)');
        $this->assertStringContainsString("\$db->exec('{$statement}');", $loop);
        // After the roles are created, and after the database is connected to as a superuser.
        $this->assertGreaterThan(
            (int)strpos($loop, '$db = connect('),
            (int)strpos($loop, $statement),
        );
        $this->assertNotFalse(strpos($loop, '$db = connect('));
    }

    public function testTheOccupanciesMigrationFailsBeforeAnyStatementWhenTheExtensionIsMissing(): void
    {
        require_once dirname(__DIR__, 3) . '/config/Migrations/20261124000001_CreateOccupancies.php';
        $migration = new class (20261124000001) extends CreateOccupancies {
            /**
             * @var list<string>
             */
            public array $executed = [];

            public function fetchRow(string $sql): array|false
            {
                return false;
            }

            public function execute(string $sql, array $params = []): int
            {
                $this->executed[] = $sql;

                return 0;
            }
        };

        try {
            $migration->up();
            $this->fail('The migration must throw when btree_gist is missing');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('"btree_gist"', $e->getMessage());
            $this->assertStringContainsString('php bin/provision_db_roles.php', $e->getMessage());
        }
        $this->assertSame([], $migration->executed, 'nothing ran before the guard');
    }

    public function testTheCiWorkflowRunsTheProvisioningCommandBeforeTheTests(): void
    {
        $workflow = (string)file_get_contents(dirname(__DIR__, 4) . '/.github/workflows/app-ci.yml');

        $provision = strpos($workflow, 'run: php bin/provision_db_roles.php --database=kualitet_app_test');
        $tests = strpos($workflow, 'run: vendor/bin/phpunit');
        $this->assertNotFalse($provision, 'the workflow runs the provisioning command');
        $this->assertNotFalse($tests, 'the workflow runs the tests');
        $this->assertGreaterThan($provision, $tests);
        // Run as the superuser, which is the only role that can create an extension.
        $step = substr($workflow, (int)strrpos(substr($workflow, 0, $provision), '- name:'), $provision);
        $this->assertStringContainsString('PGUSER: postgres', $step);
    }
}
