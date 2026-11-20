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
     * @return list<string> `table.policy.command` for every policy of people and person_developments, sorted: no
     *   policy is added, dropped or renamed by either direction.
     */
    private function policyNames(): array
    {
        $rows = $this->db()->execute(
            "SELECT tablename || '.' || policyname || '.' || cmd FROM pg_policies
             WHERE tablename IN ('people', 'person_developments') ORDER BY 1",
        )->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    private const POLICY_NAMES = [
        'people.people_delete.DELETE',
        'people.people_select.SELECT',
        'people.people_update.UPDATE',
        'person_developments.person_developments_select.SELECT',
    ];

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

    /**
     * @return array{tables: list<string>, policies: list<string>, triggers: list<string>, functions: int, constraints: list<string>}
     *   What the occupancies migration owns: the table, its policies, triggers, constraints and trigger function.
     */
    private function occupancies(): array
    {
        $names = fn(string $sql): array => array_map(static fn(array $row): string => $row[0], $this->db()->execute($sql)->fetchAll());

        return [
            'tables' => $names("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename = 'occupancies'"),
            'policies' => $names("SELECT policyname FROM pg_policies WHERE tablename = 'occupancies' ORDER BY policyname"),
            'triggers' => $names(
                "SELECT tgname FROM pg_trigger WHERE tgrelid = to_regclass('public.occupancies') AND NOT tgisinternal ORDER BY tgname",
            ),
            'functions' => (int)$this->db()->execute(
                "SELECT COUNT(*) FROM pg_proc WHERE proname = 'occupancies_guard_update' AND pronamespace = 'public'::regnamespace",
            )->fetch()[0],
            'constraints' => $names(
                "SELECT conname FROM pg_constraint WHERE conrelid = to_regclass('public.occupancies') AND contype <> 'n' ORDER BY conname",
            ),
        ];
    }

    private const BEFORE_THE_PAYER = 20261124000001;

    /**
     * Everything the payer migration (20261125000001) owns or touches, as PostgreSQL prints it: the three functions
     * (definition, owner, SECURITY DEFINER, config, privileges, volatility), the policies and triggers of both tables,
     * and the table and column privileges of `units` and the history. A missing object is null or absent.
     *
     * @return array<string, mixed>
     */
    private function payerState(): array
    {
        $functions = [];
        foreach (['unit_set_payer', 'unit_payer_changes_log', 'unit_payer_changes_reject_change'] as $name) {
            $row = $this->db()->execute(
                "SELECT pg_get_functiondef(oid) AS def, prosecdef, pg_get_userbyid(proowner) AS owner,
                        proconfig::text AS config, proacl::text AS acl, provolatile
                 FROM pg_proc WHERE proname = ? AND pronamespace = 'public'::regnamespace",
                [$name],
            )->fetch('assoc');
            $functions[$name] = $row === false ? null : $row;
        }
        $rows = fn(string $sql): array => $this->db()->execute($sql)->fetchAll();

        return [
            'functions' => $functions,
            'policies' => $rows(
                "SELECT tablename, policyname, cmd, roles::text, qual, with_check FROM pg_policies
                 WHERE tablename IN ('units', 'unit_payer_changes') ORDER BY tablename, policyname",
            ),
            'triggers' => $rows(
                "SELECT tgrelid::regclass::text, tgname, pg_get_triggerdef(oid) FROM pg_trigger
                 WHERE NOT tgisinternal AND tgrelid IN (to_regclass('public.units'), to_regclass('public.unit_payer_changes'))
                 ORDER BY 1, 2",
            ),
            'tablePrivileges' => $rows(
                "SELECT relname, relacl::text, relrowsecurity, relforcerowsecurity FROM pg_class
                 WHERE oid IN (to_regclass('public.units'), to_regclass('public.unit_payer_changes')) ORDER BY relname",
            ),
            'columnPrivileges' => $rows(
                "SELECT attrelid::regclass::text, attname, attacl::text FROM pg_attribute
                 WHERE attrelid IN (to_regclass('public.units'), to_regclass('public.unit_payer_changes'))
                   AND attnum > 0 AND NOT attisdropped AND attacl IS NOT NULL ORDER BY 1, attnum",
            ),
            'unitColumns' => $rows(
                "SELECT column_name FROM information_schema.columns WHERE table_name = 'units' ORDER BY ordinal_position",
            ),
            'unitConstraints' => $rows(
                "SELECT conname FROM pg_constraint WHERE conrelid = to_regclass('public.units') AND contype <> 'n' ORDER BY conname",
            ),
        ];
    }

    public function testRollingBackThePayerMigrationRemovesExactlyItsObjectsAndForwardBringsThemBackWithABackfill(): void
    {
        $migrations = new Migrations(['connection' => 'test_migrations']);
        $development = $this->createDevelopment('Payer rollback');
        $first = $this->seedUnit($development, null, '1');
        $second = $this->seedUnit($development, null, '2');
        $this->db()->execute("UPDATE units SET payer = 'tenant' WHERE id = ?", [$second]);

        $after = $this->payerState();
        // The control: the forward state is the one the other tests pin.
        $this->assertCount(3, array_filter($after['functions']));
        $this->assertSame('{"search_path=pg_catalog, pg_temp"}', $after['functions']['unit_set_payer']['config']);
        $this->assertCount(1, array_filter($after['policies'], static fn(array $p): bool => $p[0] === 'unit_payer_changes'));
        $this->assertContains('payer', array_column($after['unitColumns'], 0));
        $this->assertContains('units_payer_check', array_column($after['unitConstraints'], 0));
        $this->assertSame(3, (int)$this->db()->execute('SELECT COUNT(*) FROM unit_payer_changes')->fetch()[0]);

        $failure = null;
        try {
            $this->assertTrue($migrations->rollback(['target' => self::BEFORE_THE_PAYER]));
            $before = $this->payerState();

            // Functions, history table, its policy and triggers, the logging triggers of `units`, the column and its
            // CHECK are all gone; nothing else changed.
            $this->assertSame(['unit_set_payer' => null, 'unit_payer_changes_log' => null, 'unit_payer_changes_reject_change' => null], $before['functions']);
            $this->assertNull($this->db()->execute("SELECT to_regclass('public.unit_payer_changes')")->fetch()[0]);
            $this->assertSame([], array_values(array_filter($before['policies'], static fn(array $p): bool => $p[0] === 'unit_payer_changes')));
            $this->assertSame(['units_forbid_development_change'], array_column($before['triggers'], 1));
            $this->assertNotContains('payer', array_column($before['unitColumns'], 0));
            $this->assertNotContains('units_payer_check', array_column($before['unitConstraints'], 0));
            // The privileges of `units` are exactly the ones the units migration left: those of the forward state
            // (the payer had no column privilege, and the table-level privileges are untouched).
            $this->assertSame(
                array_values(array_filter($after['tablePrivileges'], static fn(array $r): bool => $r[0] === 'units')),
                $before['tablePrivileges'],
            );
            $this->assertSame(
                array_values(array_filter($after['columnPrivileges'], static fn(array $r): bool => $r[0] === 'units')),
                $before['columnPrivileges'],
            );
            $this->assertSame(
                array_values(array_filter($after['policies'], static fn(array $p): bool => $p[0] === 'units')),
                array_values(array_filter($before['policies'], static fn(array $p): bool => $p[0] === 'units')),
            );
            $this->assertSame(
                array_values(array_filter($after['triggers'], static fn(array $t): bool => $t[1] === 'units_forbid_development_change')),
                $before['triggers'],
            );
            // A unit created while the migration is rolled back has no history yet.
            $this->seedUnit($development, null, '3');
        } catch (Throwable $e) {
            $failure = $e;
        }

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

        // Forward again: every object is back exactly as before.
        $this->assertSame($after, $this->payerState());
        // Backfill: one row per existing unit (the one made while rolled back included), carrying the payer the
        // column came back with (the default: a rollback drops the stored payers), no user, and the creation time.
        $rows = $this->db()->execute(
            'SELECT h.unit_id, h.payer, h.changed_by_user_id, h.changed_at = u.created, h.development_id = u.development_id
             FROM unit_payer_changes h JOIN units u ON u.id = h.unit_id ORDER BY h.unit_id',
        )->fetchAll();
        $this->assertCount(3, $rows);
        $this->assertSame($first, (int)$rows[0][0]);
        foreach ($rows as $row) {
            $this->assertSame(['owner', null, true, true], [$row[1], $row[2], (bool)$row[3], (bool)$row[4]]);
        }
        $this->assertSame(3, (int)$this->db()->execute('SELECT COUNT(*) FROM units')->fetch()[0]);
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
        // The occupancies migration (5a): table, four policies, two triggers, its trigger function and constraints.
        $occupanciesAfterUp = $this->occupancies();
        $this->assertSame(['occupancies'], $occupanciesAfterUp['tables']);
        $this->assertCount(4, $occupanciesAfterUp['policies']);
        $this->assertSame(['occupancies_forbid_development_change', 'occupancies_guard_update'], $occupanciesAfterUp['triggers']);
        $this->assertSame(1, $occupanciesAfterUp['functions']);
        // PostgreSQL 18 also lists NOT NULL constraints; the named ones are what the migration declares.
        $this->assertSame([
            'occupancies_development_id_fkey',
            'occupancies_no_overlap_per_person',
            'occupancies_one_primary_per_role',
            'occupancies_period_check',
            'occupancies_person_development_fkey',
            'occupancies_pkey',
            'occupancies_role_check',
            'occupancies_unit_fkey',
        ], $occupanciesAfterUp['constraints']);
        $this->assertContains('administrator:occupancies.manage', $matrixAfterUp);
        $this->assertContains('data_entry:occupancies.manage', $matrixAfterUp);
        $this->assertContains('occupancies.manage', $codesAfterUp);
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
        $this->assertSame(self::POLICY_NAMES, $this->policyNames());
        $this->assertContains('data_entry:people.manage', $matrixAfterUp);
        $this->assertContains('supervisor:people.view', $matrixAfterUp);

        $expectedAfterDown = array_values(array_filter(
            $matrixAfterUp,
            static fn(string $pair): bool => !str_ends_with($pair, ':units.manage')
                && !str_ends_with($pair, ':occupancies.manage')
                && !str_contains($pair, ':people.'),
        ));
        $expectedAfterDown[] = 'resident:units.view';
        sort($expectedAfterDown);

        $failure = null;
        try {
            $this->assertTrue($migrations->rollback(['target' => self::BEFORE_THE_THREE]));

            $this->assertFalse($this->functionExists());
            // Occupancies, with everything that came with them, are gone; the btree_gist extension stays.
            $this->assertSame(1, (int)$this->db()->execute("SELECT COUNT(*) FROM pg_extension WHERE extname = 'btree_gist'")->fetch()[0]);
            $this->assertSame(
                ['tables' => [], 'policies' => [], 'triggers' => [], 'functions' => 0, 'constraints' => []],
                $this->occupancies(),
            );
            // Sections and units, with their policies and triggers, are gone.
            $this->assertSame(['tables' => [], 'policies' => 0, 'triggers' => 0], $this->sectionsAndUnits());
            // Only the rows the migrations own changed: units.manage went away, one resident row came back.
            $this->assertSame($expectedAfterDown, $this->matrix());
            $this->assertSame(
                array_values(array_diff($codesAfterUp, ['units.manage', 'people.view', 'people.manage', 'occupancies.manage'])),
                $this->codes(),
            );
            // The people migration restored exactly what the original migrations left: definition, owner, config and
            // privileges of person_create, USING and WITH CHECK of the policy, and no scope function.
            $this->assertSame($peopleBeforeUp, $this->peopleAccess());
            // The read policies are back to the dev_ids() ones 20261119000003 created: same USING, command and roles.
            $this->assertSame($this->originalReadPolicies(), $this->readPolicies());
            $this->assertSame(self::POLICY_NAMES, $this->policyNames());
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
        $this->assertSame($occupanciesAfterUp, $this->occupancies());
        $this->assertSame($peopleAfterUp, $this->peopleAccess());
        $this->assertSame($this->expectedReadPolicies(), $this->readPolicies());
        $this->assertSame(self::POLICY_NAMES, $this->policyNames());
        $this->assertSame($matrixAfterUp, $this->matrix());
        $this->assertSame($codesAfterUp, $this->codes());
    }
}
