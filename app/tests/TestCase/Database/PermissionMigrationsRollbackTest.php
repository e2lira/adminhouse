<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Test\Support\DatabaseTestCase;
use Migrations\Migrations;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Throwable;

/**
 * AD6, AD13: the `permission_dev_ids`, `units.manage` and resident-revoke migrations undo exactly what they did, and
 * so do the later `sections`, `units` and people migrations (task 7.6, 10.8: tables, policies, triggers, functions and
 * seeds go away and come back).
 *
 * Rolls them back on the test database and forwards again, comparing the role/permission rows. The forward run
 * runs even when an assertion fails, so a failure cannot leave later tests on a half-migrated schema, and a failure
 * of that restore never hides the original one. It changes the shared test schema: not parallel-safe, so it has its own
 * group (`migration-rollback`, run alone with `--group migration-rollback`). It stays in the default run.
 */
#[Group('migration-rollback')]
class PermissionMigrationsRollbackTest extends DatabaseTestCase
{
    private const BEFORE_THE_THREE = 20261120000001;

    /**
     * @return list<string> `role:permission` for every `role_permissions` row, sorted.
     */
    private function matrix(): array
    {
        $rows = $this->db()->execute(
            "SELECT r.code || ':' || p.code
             FROM role_permissions rp
             JOIN roles r ON r.id = rp.role_id
             JOIN permissions p ON p.id = rp.permission_id
             ORDER BY 1",
        )->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    /**
     * @return list<string> Permission codes, sorted.
     */
    private function codes(): array
    {
        $rows = $this->db()->execute('SELECT code FROM permissions ORDER BY code')->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    private function functionExists(): bool
    {
        return (int)$this->db()->execute(
            "SELECT COUNT(*) FROM pg_proc WHERE proname = 'permission_dev_ids' AND pronamespace = 'public'::regnamespace",
        )->fetch()[0] === 1;
    }

    /**
     * @return array{createUses: string, updatePolicyUses: string, scopeFunction: bool} What the people migration owns:
     *   which helper `person_create` and the people UPDATE policy call, and whether the new function exists.
     */
    private function peopleAccess(): array
    {
        $create = (string)$this->db()->execute(
            "SELECT pg_get_functiondef(oid) FROM pg_proc
             WHERE proname = 'person_create' AND pronamespace = 'public'::regnamespace",
        )->fetch()[0];
        $policy = (string)$this->db()->execute(
            "SELECT qual FROM pg_policies WHERE tablename = 'people' AND policyname = 'people_update'",
        )->fetch()[0];

        return [
            'createUses' => str_contains($create, 'permission_dev_ids') ? 'permission_dev_ids' : (
                str_contains($create, 'admin_dev_ids') ? 'admin_dev_ids' : 'neither'
            ),
            'updatePolicyUses' => str_contains($policy, 'person_in_permission_scope')
                ? 'person_in_permission_scope'
                : (str_contains($policy, 'person_in_admin_scope') ? 'person_in_admin_scope' : 'neither'),
            'scopeFunction' => (int)$this->db()->execute(
                "SELECT COUNT(*) FROM pg_proc
                 WHERE proname = 'person_in_permission_scope' AND pronamespace = 'public'::regnamespace",
            )->fetch()[0] === 1,
        ];
    }

    /**
     * @return array{tables: list<string>, policies: int, triggers: int} What the sections and units migrations own.
     */
    private function sectionsAndUnits(): array
    {
        $tables = array_map(
            static fn(array $row): string => $row[0],
            $this->db()->execute(
                "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename IN ('sections', 'units')
                 ORDER BY tablename",
            )->fetchAll(),
        );
        $policies = (int)$this->db()->execute(
            "SELECT COUNT(*) FROM pg_policies WHERE tablename IN ('sections', 'units')",
        )->fetch()[0];
        $triggers = (int)$this->db()->execute(
            "SELECT COUNT(*) FROM pg_trigger WHERE NOT tgisinternal AND tgname IN
                ('sections_forbid_development_change', 'units_forbid_development_change')",
        )->fetch()[0];

        return ['tables' => $tables, 'policies' => $policies, 'triggers' => $triggers];
    }

    public function testRollingBackRestoresTheMatrixTheThreeMigrationsFoundAndForwardRestoresTheirs(): void
    {
        $migrations = new Migrations(['connection' => 'test_migrations']);
        $matrixAfterUp = $this->matrix();
        $codesAfterUp = $this->codes();

        // The control: the forward state is the one the other tests rely on.
        $sectionsAndUnitsAfterUp = $this->sectionsAndUnits();
        $this->assertSame(['tables' => ['sections', 'units'], 'policies' => 8, 'triggers' => 2], $sectionsAndUnitsAfterUp);
        $this->assertTrue($this->functionExists());
        $this->assertContains('administrator:units.manage', $matrixAfterUp);
        $this->assertContains('data_entry:units.manage', $matrixAfterUp);
        $this->assertNotContains('resident:units.view', $matrixAfterUp);
        $peopleAfterUp = $this->peopleAccess();
        $this->assertSame(
            ['createUses' => 'permission_dev_ids', 'updatePolicyUses' => 'person_in_permission_scope', 'scopeFunction' => true],
            $peopleAfterUp,
        );
        $this->assertContains('data_entry:people.manage', $matrixAfterUp);
        $this->assertContains('supervisor:people.view', $matrixAfterUp);

        $expectedAfterDown = array_values(array_filter(
            $matrixAfterUp,
            static fn(string $pair): bool => !str_ends_with($pair, ':units.manage')
                && !str_contains($pair, ':people.'),
        ));
        $expectedAfterDown[] = 'resident:units.view';
        sort($expectedAfterDown);

        $failure = null;
        try {
            $this->assertTrue($migrations->rollback(['target' => self::BEFORE_THE_THREE]));

            $this->assertFalse($this->functionExists());
            // Sections and units, with their policies and triggers, are gone.
            $this->assertSame(['tables' => [], 'policies' => 0, 'triggers' => 0], $this->sectionsAndUnits());
            // Only the rows the migrations own changed: units.manage went away, one resident row came back.
            $this->assertSame($expectedAfterDown, $this->matrix());
            $this->assertSame(
                array_values(array_diff($codesAfterUp, ['units.manage', 'people.view', 'people.manage'])),
                $this->codes(),
            );
            // The people migration restored the helper calls the original migrations used.
            $this->assertSame(
                ['createUses' => 'admin_dev_ids', 'updatePolicyUses' => 'person_in_admin_scope', 'scopeFunction' => false],
                $this->peopleAccess(),
            );
        } catch (Throwable $e) {
            $failure = $e;
        }

        // Always bring the schema forward again; the first failure, if any, is the one reported.
        $restoreError = null;
        try {
            $restored = $migrations->migrate();
        } catch (Throwable $e) {
            $restored = false;
            $restoreError = $e;
        }
        if ($failure !== null) {
            throw $failure;
        }
        if (!$restored) {
            throw new RuntimeException('The migrations could not be applied again', 0, $restoreError);
        }

        $this->assertTrue($this->functionExists());
        $this->assertSame($sectionsAndUnitsAfterUp, $this->sectionsAndUnits());
        $this->assertSame($peopleAfterUp, $this->peopleAccess());
        $this->assertSame($matrixAfterUp, $this->matrix());
        $this->assertSame($codesAfterUp, $this->codes());
    }
}
