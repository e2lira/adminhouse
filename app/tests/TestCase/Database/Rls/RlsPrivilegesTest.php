<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

/**
 * What the roles are, what they own, and what the runtime role cannot do to escape the policies.
 */
class RlsPrivilegesTest extends RlsTestCase
{
    /**
     * @return list<string> Application tables (everything except the migration bookkeeping).
     */
    private function applicationTables(): array
    {
        $rows = $this->db()->execute(
            "SELECT c.relname
             FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
             WHERE n.nspname = 'public' AND c.relkind IN ('r', 'p')
               AND c.relname NOT IN ('cake_migrations')
               AND c.relname NOT LIKE '%phinxlog%'
             ORDER BY c.relname",
        )->fetchAll();

        return array_map(static fn(array $row): string => $row[0], $rows);
    }

    public function testTheRolesHaveTheDesignedAttributes(): void
    {
        $roles = $this->db()->execute(
            "SELECT rolname, rolsuper, rolbypassrls, rolcanlogin, rolcreaterole, rolcreatedb
             FROM pg_roles WHERE rolname IN ('kualitet_app', 'kualitet_owner', 'kualitet_definer')",
        )->fetchAll('assoc');
        $byName = array_column($roles, null, 'rolname');

        $this->assertCount(3, $byName);
        $this->assertFalse($byName['kualitet_app']['rolbypassrls']);
        $this->assertTrue($byName['kualitet_app']['rolcanlogin']);
        $this->assertTrue($byName['kualitet_owner']['rolbypassrls']);
        $this->assertTrue($byName['kualitet_definer']['rolbypassrls']);
        $this->assertFalse($byName['kualitet_definer']['rolcanlogin']);
        foreach ($byName as $name => $role) {
            $this->assertFalse($role['rolsuper'], $name);
            $this->assertFalse($role['rolcreaterole'], $name);
            $this->assertFalse($role['rolcreatedb'], $name);
        }
    }

    public function testTheSuiteRunsAsTheRuntimeRole(): void
    {
        $this->assertSame('kualitet_app', $this->appDb()->execute('SELECT current_user')->fetch()[0]);
        $this->assertSame('kualitet_owner', $this->db()->execute('SELECT current_user')->fetch()[0]);
    }

    public function testTheRuntimeRoleOwnsNothing(): void
    {
        $owned = $this->db()->execute(
            "SELECT c.relname FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
             WHERE n.nspname = 'public' AND pg_get_userbyid(c.relowner) = 'kualitet_app'",
        )->fetchAll();
        $functions = $this->db()->execute(
            "SELECT p.proname FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
             WHERE n.nspname = 'public' AND pg_get_userbyid(p.proowner) = 'kualitet_app'",
        )->fetchAll();

        $this->assertSame([], $owned);
        $this->assertSame([], $functions);
    }

    public function testEveryTableHasForcedRowLevelSecurity(): void
    {
        $tables = $this->applicationTables();
        $this->assertContains('developments', $tables);
        $this->assertContains('role_assignment_events', $tables);

        $rows = $this->db()->execute(
            "SELECT c.relname, c.relrowsecurity, c.relforcerowsecurity
             FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
             WHERE n.nspname = 'public' AND c.relname = ANY(?::text[])",
            ['{' . implode(',', $tables) . '}'],
        )->fetchAll('assoc');

        $this->assertCount(count($tables), $rows);
        foreach ($rows as $row) {
            $this->assertTrue($row['relrowsecurity'], $row['relname'] . ' must ENABLE ROW LEVEL SECURITY');
            $this->assertTrue($row['relforcerowsecurity'], $row['relname'] . ' must FORCE ROW LEVEL SECURITY');
        }
    }

    public function testPublicHasNoPrivilegeOnAnyTable(): void
    {
        foreach ($this->applicationTables() as $table) {
            $grants = $this->db()->execute(
                'SELECT 1 FROM information_schema.role_table_grants WHERE table_name = ? AND grantee = ?',
                [$table, 'PUBLIC'],
            )->fetch();
            $this->assertFalse($grants, $table);
        }
    }

    public function testEveryDefinerFunctionIsHardened(): void
    {
        $functions = $this->db()->execute(
            "SELECT p.oid::regprocedure::text AS signature, pg_get_userbyid(p.proowner) AS owner,
                    p.proconfig::text AS config, p.prosecdef,
                    has_function_privilege('public', p.oid, 'EXECUTE') AS public_execute,
                    has_function_privilege('kualitet_app', p.oid, 'EXECUTE') AS app_execute,
                    t.oid IS NOT NULL AS is_trigger_function
             FROM pg_proc p
             JOIN pg_namespace n ON n.oid = p.pronamespace
             LEFT JOIN pg_type t ON t.oid = p.prorettype AND t.typname = 'trigger'
             WHERE n.nspname = 'public' AND p.prosecdef",
        )->fetchAll('assoc');

        $this->assertGreaterThan(20, count($functions));
        foreach ($functions as $function) {
            $name = $function['signature'];
            $this->assertSame('kualitet_definer', $function['owner'], $name);
            $this->assertStringContainsString('search_path=pg_catalog, pg_temp', $function['config'], $name);
            $this->assertFalse($function['public_execute'], $name . ' must not be executable by PUBLIC');
            if (str_starts_with($name, 'user_create(')) {
                // Bootstrap only: reached through the owner login, never by the runtime role.
                $this->assertFalse($function['app_execute'], $name . ' must not be executable by the runtime role');
            } elseif (!$function['is_trigger_function']) {
                $this->assertTrue($function['app_execute'], $name . ' must be executable by the runtime role');
            }
        }
    }

    public function testTheHelpersAreStable(): void
    {
        $rows = $this->db()->execute(
            "SELECT proname, provolatile FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
             WHERE n.nspname = 'public' AND proname IN ('dev_ids', 'admin_dev_ids', 'is_super', 'person_id', 'session_user_id')",
        )->fetchAll('assoc');

        $this->assertCount(5, $rows);
        foreach ($rows as $row) {
            $this->assertSame('s', $row['provolatile'], $row['proname']);
        }
    }

    public function testTheDefinerRoleCannotLogIn(): void
    {
        $this->assertFalse((bool)$this->db()->execute(
            "SELECT rolcanlogin FROM pg_roles WHERE rolname = 'kualitet_definer'",
        )->fetch()[0]);
    }

    // escalation attempts -----------------------------------------------------------------------------------------

    public function testTheRuntimeRoleCannotAssumeAnotherRole(): void
    {
        $this->actAs($this->resident);

        $this->assertRejected('SET LOCAL ROLE kualitet_owner');
        $this->assertRejected('SET LOCAL ROLE kualitet_definer');
        $this->assertRejected('SET LOCAL SESSION AUTHORIZATION kualitet_owner');
        $this->assertRejected('SET LOCAL ROLE postgres');
    }

    public function testTheDefinerRoleCannotCreateObjectsOutsideAMigration(): void
    {
        // CREATE on the schema is granted to the definer role only for the duration of the statements that hand a
        // function over to it (ALTER FUNCTION ... OWNER TO needs it) and revoked right after.
        $this->assertFalse(
            (bool)$this->db()->execute("SELECT has_schema_privilege('kualitet_definer', 'public', 'CREATE')")->fetch()[0],
        );
    }

    public function testFunctionsCreatedLaterAreNotExecutableByPublicByDefault(): void
    {
        $rows = $this->db()->execute(
            "SELECT pg_get_userbyid(d.defaclrole) AS owner, d.defaclacl::text AS acl
             FROM pg_default_acl d
             WHERE d.defaclobjtype = 'f'
               AND d.defaclnamespace = 0
               AND pg_get_userbyid(d.defaclrole) IN ('kualitet_owner', 'kualitet_definer')",
        )->fetchAll('assoc');

        $owners = array_column($rows, 'owner');
        sort($owners);
        $this->assertSame(['kualitet_definer', 'kualitet_owner'], $owners);
        foreach ($rows as $row) {
            // No `=X/...` entry: PUBLIC gets no EXECUTE on new functions.
            $this->assertDoesNotMatchRegularExpression('/[{,]=X/', $row['acl'], $row['owner']);
        }
    }

    public function testTheRuntimeRoleCannotTurnRowSecurityOffOrAlterTables(): void
    {
        $this->actAs($this->resident);

        $this->assertRejected('ALTER TABLE developments DISABLE ROW LEVEL SECURITY');
        $this->assertRejected('ALTER TABLE developments NO FORCE ROW LEVEL SECURITY');
        $this->assertRejected('DROP POLICY developments_select ON developments');
        $this->assertRejected('CREATE POLICY open ON developments FOR SELECT USING (true)');
        $this->assertRejected('ALTER ROLE kualitet_app BYPASSRLS');
        $this->assertRejected('CREATE FUNCTION public.dev_ids() RETURNS bigint[] LANGUAGE sql AS $$ SELECT NULL::bigint[] $$');
        $this->assertRejected('CREATE TABLE public.sneaky (id int)');
        $this->assertRejected('ALTER FUNCTION is_super() OWNER TO kualitet_app');
    }

    public function testDisablingRowSecurityForTheSessionNeverBypassesIt(): void
    {
        $this->actAs($this->resident);
        $this->appDb()->execute('SET LOCAL row_security = off');

        // With row_security off PostgreSQL refuses the query instead of showing the rows.
        $this->assertRejected('SELECT id FROM developments');
    }

    public function testASessionValueNamedAfterAHelperChangesNothing(): void
    {
        $this->actAs($this->resident);

        $this->appDb()->execute("SELECT set_config('app.is_super', 'true', true), set_config('app.admin_dev_ids', ?, true)", [(string)$this->devA]);

        $this->assertRejected('INSERT INTO developments (type, name, section_label, number_label, created, modified)
            VALUES (?, ?, ?, ?, NOW(), NOW())', ['condominium', 'X', 'T', 'D']);
        $this->assertDenied('UPDATE people SET phone = ? WHERE id = ?', ['1', $this->personShared]);
    }

    public function testTheRuntimeRoleCannotCreateTemporaryObjectsToShadowTheHelpers(): void
    {
        $this->actAs($this->resident);

        // No TEMP privilege on the database, and the helpers pin their search_path besides.
        $this->assertRejected('CREATE TEMP TABLE users (id bigint, status text)');
        $this->assertRejected('CREATE TEMP TABLE role_assignments (user_id bigint)');
    }

    public function testClaimingAnotherUserIdIsTheOnlyLeverAndItIsApplicationTrust(): void
    {
        // Documented limit (ADR-0005 s.1): the database cannot authenticate the user id the application sets. A
        // compromised application (SQL injection with the runtime role included) can claim any user. What it can
        // never do is invent rights: every right derives from a real, active user's assignments. Authenticating
        // that id is the job of the application (sign-in, TOTP, sessions).
        $this->actAs($this->superUser);

        $this->assertTrue((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
    }

    public function testTheHelpersDoNotLeakOtherUsers(): void
    {
        // Calling a helper directly returns only what the session already implies.
        $this->actAs($this->resident);

        $this->assertSame([$this->devA], $this->ids('SELECT unnest(dev_ids())'));
        $this->assertSame([], $this->ids('SELECT unnest(admin_dev_ids())'));
        $this->assertFalse((bool)$this->appDb()->execute('SELECT is_super()')->fetch()[0]);
        $this->assertSame($this->personResident, (int)$this->appDb()->execute('SELECT person_id()')->fetch()[0]);
    }

    public function testTheRuntimeRoleCannotCallTriggerFunctionsDirectly(): void
    {
        $this->actAs($this->resident);

        $this->assertRejected('SELECT auth_events_reject_change()');
        $this->assertRejected('SELECT role_assignments_limit_super_administrators()');
    }
}
