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
 * so do the later `sections`, `units` and people migrations (scope and read policies) (task 7.6, 10.8: tables, policies, triggers, functions and
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
     * The exact state the people migration owns: the full definition and the security attributes of both functions
     * (null when a function does not exist) and the USING and WITH CHECK of the people UPDATE policy.
     *
     * @return array{functions: array<string, array<string, mixed>|null>, policy: array<string, mixed>}
     */
    private function peopleAccess(): array
    {
        $functions = [];
        foreach (['person_create', 'person_in_permission_scope'] as $name) {
            $row = $this->db()->execute(
                "SELECT pg_get_functiondef(oid) AS def, prosecdef, pg_get_userbyid(proowner) AS owner,
                        proconfig::text AS config, proacl::text AS acl, provolatile
                 FROM pg_proc WHERE proname = ? AND pronamespace = 'public'::regnamespace",
                [$name],
            )->fetch('assoc');
            $functions[$name] = $row === false ? null : $row;
        }
        $policy = $this->policy('people', 'people_update');

        return ['functions' => $functions, 'policy' => $policy];
    }

    /**
     * @param string $table Table name.
     * @param string $name Policy name.
     * @return array<string, mixed> USING, WITH CHECK, command and roles of a policy.
     */
    private function policy(string $table, string $name): array
    {
        $policy = $this->db()->execute(
            'SELECT qual, with_check, cmd, roles::text AS roles FROM pg_policies WHERE tablename = ? AND policyname = ?',
            [$table, $name],
        )->fetch('assoc');
        $this->assertIsArray($policy, $name);

        return $policy;
    }

    /**
     * The people read policies the 4a-ii migration owns (A2): the exact state it leaves and the exact state the
     * original migration (20261119000003) left, pinned as PostgreSQL prints them.
     *
     * @return array{people_select: array<string, mixed>, person_developments_select: array<string, mixed>}
     */
    private function readPolicies(): array
    {
        return [
            'people_select' => $this->policy('people', 'people_select'),
            'person_developments_select' => $this->policy('person_developments', 'person_developments_select'),
        ];
    }

    /**
     * @return array<string, array<string, string|null>> The people read policies 20261119000003 created, verbatim.
     */
    private function originalReadPolicies(): array
    {
        $dev = '( SELECT dev_ids() AS dev_ids)::bigint[]';

        return [
            'people_select' => [
                'qual' => "((id = ( SELECT person_id() AS person_id)) OR ( SELECT is_super() AS is_super) OR (EXISTS ( SELECT 1\n"
                    . "   FROM person_developments pd\n"
                    . "  WHERE ((pd.person_id = people.id) AND (pd.development_id = ANY ({$dev}))))))",
                'with_check' => null,
                'cmd' => 'SELECT',
                'roles' => '{kualitet_app}',
            ],
            'person_developments_select' => [
                'qual' => "((development_id = ANY ({$dev})) OR (person_id = ( SELECT person_id() AS person_id))"
                    . ' OR ( SELECT is_super() AS is_super))',
                'with_check' => null,
                'cmd' => 'SELECT',
                'roles' => '{kualitet_app}',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string|null>> The people read policies 20261123000002 creates, verbatim.
     */
    private function expectedReadPolicies(): array
    {
        $view = "( SELECT permission_dev_ids('people.view'::text) AS permission_dev_ids)::bigint[]";

        return [
            'people_select' => [
                'qual' => "((id = ( SELECT person_id() AS person_id)) OR ( SELECT is_super() AS is_super) OR (EXISTS ( SELECT 1\n"
                    . "   FROM person_developments pd\n"
                    . "  WHERE ((pd.person_id = people.id) AND (pd.development_id = ANY ({$view}))))))",
                'with_check' => null,
                'cmd' => 'SELECT',
                'roles' => '{kualitet_app}',
            ],
            'person_developments_select' => [
                'qual' => "((development_id = ANY ({$view})) OR (person_id = ( SELECT person_id() AS person_id))"
                    . ' OR ( SELECT is_super() AS is_super))',
                'with_check' => null,
                'cmd' => 'SELECT',
                'roles' => '{kualitet_app}',
            ],
        ];
    }

    /**
     * What the original migrations (20261119000002 and 20261119000003) left: the same snapshot with the helper calls
     * they used, and no `person_in_permission_scope`.
     *
     * @param array<string, mixed> $after Snapshot taken with the people migration applied.
     * @return array<string, mixed>
     */
    private function originalPeopleAccess(array $after): array
    {
        $swap = static fn(mixed $text): mixed => is_string($text)
            ? str_replace(
                ["public.permission_dev_ids('people.manage')", "person_in_permission_scope(id, 'people.manage'::text)"],
                ['public.admin_dev_ids()', 'person_in_admin_scope(id)'],
                $text,
            )
            : $text;
        $create = $after['functions']['person_create'];
        $create['def'] = $swap($create['def']);

        return [
            'functions' => ['person_create' => $create, 'person_in_permission_scope' => null],
            'policy' => array_map($swap, $after['policy']),
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
        $this->assertNotNull($peopleAfterUp['functions']['person_in_permission_scope']);
        $this->assertStringContainsString('permission_dev_ids', $peopleAfterUp['functions']['person_create']['def']);
        $this->assertStringContainsString('person_in_permission_scope', $peopleAfterUp['policy']['qual']);
        $this->assertStringContainsString('person_in_permission_scope', $peopleAfterUp['policy']['with_check']);
        $peopleBeforeUp = $this->originalPeopleAccess($peopleAfterUp);
        // The control: the read policies follow people.view (A2), exactly as pinned.
        $this->assertSame($this->expectedReadPolicies(), $this->readPolicies());
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
            // The people migration restored exactly what the original migrations left: definition, owner, config and
            // privileges of person_create, USING and WITH CHECK of the policy, and no scope function.
            $this->assertSame($peopleBeforeUp, $this->peopleAccess());
            // The read policies are back to the dev_ids() ones 20261119000003 created: same USING, command and roles.
            $this->assertSame($this->originalReadPolicies(), $this->readPolicies());
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
        $this->assertSame($this->expectedReadPolicies(), $this->readPolicies());
        $this->assertSame($matrixAfterUp, $this->matrix());
        $this->assertSame($codesAfterUp, $this->codes());
    }
}
