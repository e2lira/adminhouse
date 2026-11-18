<?php
declare(strict_types=1);

use App\Database\RowLevelSecurity as Rls;
use Migrations\BaseMigration;

/**
 * RLS-SEC-001, step 1: session helpers and privileged trigger functions (ADR-0005 section 1, layer 2).
 *
 * Run it with the migration login (`bin/cake migrations migrate -c migrations`) after `bin/provision_db_roles.php`.
 *
 * - `session_user_id()`, `dev_ids()`, `admin_dev_ids()`, `is_super()`, `person_id()` and `person_in_admin_scope()`
 *   are STABLE SECURITY DEFINER functions owned by the NOLOGIN, BYPASSRLS definer role, with a pinned search_path.
 *   They compute access from role_assignments and users on each transaction; the application only sets
 *   `app.user_id` and `app.context`.
 * - The existing trigger functions become definer functions owned by the definer role too, with qualified names, so
 *   the two-account super-administrator limit still counts every row once the application role can see only its own.
 * - `business_today()` gets a pinned search_path.
 * - The definer role receives the table privileges its functions need.
 */
class CreateRlsHelpers extends BaseMigration
{
    private const TODAY = 'public.business_today()';

    public function up(): void
    {
        // business_today() is called from definer functions and from the runtime role's triggers: pin its path.
        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.business_today() RETURNS date
            LANGUAGE sql STABLE SET search_path = pg_catalog, pg_temp AS $$
                SELECT (pg_catalog.now() AT TIME ZONE 'America/Mexico_City')::date
            $$
        SQL);
        $this->execute('REVOKE ALL ON FUNCTION public.business_today() FROM PUBLIC');
        $this->execute('GRANT EXECUTE ON FUNCTION public.business_today() TO ' . Rls::ROLE_APP . ', ' . Rls::ROLE_DEFINER);

        foreach ($this->definerTablePrivileges() as $table => $privileges) {
            $this->execute(Rls::grantDefiner($table, $privileges));
        }

        $today = self::TODAY;
        $activeAssignment = "ra.starts_on <= {$today} AND (ra.ends_on IS NULL OR ra.ends_on >= {$today})";

        // The user of the session, only while the account is active and the request context is set.
        $this->define('session_user_id', [], 'bigint', 'sql', 'STABLE', <<<'SQL'
            SELECT u.id
            FROM public.users u
            WHERE pg_catalog.current_setting('app.context', true) = 'request'
              AND u.status = 'active'
              AND u.id = (CASE
                  WHEN NULLIF(pg_catalog.current_setting('app.user_id', true), '') ~ '^[0-9]{1,18}$'
                  THEN NULLIF(pg_catalog.current_setting('app.user_id', true), '')::bigint
              END)
        SQL);

        // Developments where the session has an active assignment (any role, read-only included).
        $this->define('dev_ids', [], 'bigint[]', 'sql', 'STABLE', <<<SQL
            SELECT CASE pg_catalog.current_setting('app.context', true)
                WHEN 'request' THEN COALESCE((
                    SELECT pg_catalog.array_agg(DISTINCT ra.development_id)
                    FROM public.role_assignments ra
                    WHERE ra.user_id = public.session_user_id()
                      AND ra.development_id IS NOT NULL
                      AND {$activeAssignment}
                ), ARRAY[]::bigint[])
                WHEN 'job' THEN COALESCE((
                    SELECT pg_catalog.array_agg(DISTINCT x::bigint)
                    FROM pg_catalog.unnest(pg_catalog.string_to_array(
                        NULLIF(pg_catalog.current_setting('app.job_dev_ids', true), ''), ','
                    )) AS x
                    WHERE x ~ '^[0-9]{1,18}$'
                ), ARRAY[]::bigint[])
                ELSE ARRAY[]::bigint[]
            END
        SQL);

        // Developments the session administers: an active, writable `administrator` assignment. Never in a job.
        $this->define('admin_dev_ids', [], 'bigint[]', 'sql', 'STABLE', <<<SQL
            SELECT COALESCE((
                SELECT pg_catalog.array_agg(DISTINCT ra.development_id)
                FROM public.role_assignments ra
                JOIN public.roles r ON r.id = ra.role_id
                WHERE pg_catalog.current_setting('app.context', true) = 'request'
                  AND ra.user_id = public.session_user_id()
                  AND r.code = 'administrator'
                  AND NOT ra.read_only
                  AND ra.development_id IS NOT NULL
                  AND {$activeAssignment}
            ), ARRAY[]::bigint[])
        SQL);

        // Whether the session user holds an active, writable super_administrator assignment.
        $this->define('is_super', [], 'boolean', 'sql', 'STABLE', <<<SQL
            SELECT EXISTS (
                SELECT 1
                FROM public.role_assignments ra
                JOIN public.roles r ON r.id = ra.role_id
                WHERE ra.user_id = public.session_user_id()
                  AND r.code = 'super_administrator'
                  AND NOT ra.read_only
                  AND {$activeAssignment}
            )
        SQL);

        $this->define('person_id', [], 'bigint', 'sql', 'STABLE', <<<'SQL'
            SELECT u.person_id FROM public.users u WHERE u.id = public.session_user_id()
        SQL);

        // True when the person has links and every one of them is a development the session administers. The
        // runtime role cannot see links to other developments, so the question needs a definer function.
        $this->define('person_in_admin_scope', ['p_person_id' => 'bigint'], 'boolean', 'sql', 'STABLE', <<<'SQL'
            SELECT EXISTS (
                       SELECT 1 FROM public.person_developments pd WHERE pd.person_id = p_person_id
                   )
               AND NOT EXISTS (
                       SELECT 1 FROM public.person_developments pd
                       WHERE pd.person_id = p_person_id
                         AND NOT (pd.development_id = ANY (public.admin_dev_ids()))
                   )
        SQL);

        $this->rewriteTriggerFunctions(true);

        // Generic guard for catalogue tables (RowLevelSecurity::catalogTable): a row never changes development.
        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.forbid_development_id_change() RETURNS trigger
            LANGUAGE plpgsql SET search_path = pg_catalog, pg_temp AS $$
            BEGIN
                IF NEW.development_id IS DISTINCT FROM OLD.development_id THEN
                    RAISE EXCEPTION 'The development of a catalogue item cannot be changed'
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        foreach (Rls::giveToDefiner('public.forbid_development_id_change()') as $sql) {
            $this->execute($sql);
        }
        $this->execute('REVOKE ALL ON FUNCTION public.forbid_development_id_change() FROM PUBLIC');
    }

    public function down(): void
    {
        $this->execute('DROP FUNCTION public.forbid_development_id_change()');
        $this->rewriteTriggerFunctions(false);

        foreach (['person_in_admin_scope' => ['bigint'], 'is_super' => [], 'admin_dev_ids' => [], 'dev_ids' => [],
            'person_id' => [], 'session_user_id' => []] as $name => $types) {
            $this->execute(Rls::dropFunction($name, $types));
        }

        foreach (array_keys($this->definerTablePrivileges()) as $table) {
            $this->execute(sprintf('REVOKE ALL ON %s FROM %s', $table, Rls::ROLE_DEFINER));
        }

        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.business_today() RETURNS date
            LANGUAGE sql STABLE AS $$
                SELECT (now() AT TIME ZONE 'America/Mexico_City')::date
            $$
        SQL);
        $this->execute('GRANT EXECUTE ON FUNCTION public.business_today() TO PUBLIC');
    }

    /**
     * Privileges of the definer role: what its functions and triggers read and write.
     *
     * @return array<string, string>
     */
    private function definerTablePrivileges(): array
    {
        return [
            'users' => 'SELECT, INSERT, UPDATE, DELETE',
            'user_credentials' => 'SELECT, INSERT, UPDATE, DELETE',
            'user_totp' => 'SELECT, INSERT, UPDATE, DELETE',
            'user_backup_codes' => 'SELECT, INSERT, UPDATE, DELETE',
            'people' => 'SELECT, INSERT, UPDATE, DELETE',
            'person_developments' => 'SELECT, INSERT, UPDATE, DELETE',
            'role_assignments' => 'SELECT, INSERT, UPDATE, DELETE',
            'developments' => 'SELECT',
            'roles' => 'SELECT',
            'permissions' => 'SELECT',
            'role_permissions' => 'SELECT',
            'auth_events' => 'SELECT, INSERT',
            'role_assignment_events' => 'SELECT',
        ];
    }

    /**
     * @param string $name Function name.
     * @param array<string, string> $parameters Parameters.
     * @param string $returns Return type.
     * @param string $language Language.
     * @param string $volatility Volatility.
     * @param string $body Body.
     * @return void
     */
    private function define(
        string $name,
        array $parameters,
        string $returns,
        string $language,
        string $volatility,
        string $body,
    ): void {
        foreach (Rls::definerFunction($name, $parameters, $returns, $language, $volatility, $body) as $sql) {
            $this->execute($sql);
        }
    }

    /**
     * Turn the existing trigger functions into hardened definer functions (up) or back (down).
     *
     * @param bool $hardened Hardened definer versions when true; the originals when false.
     * @return void
     */
    private function rewriteTriggerFunctions(bool $hardened): void
    {
        $secure = $hardened ? 'SECURITY DEFINER SET search_path = pg_catalog, pg_temp' : '';
        $roles = $hardened ? 'public.roles' : 'roles';
        $assignments = $hardened ? 'public.role_assignments' : 'role_assignments';
        $today = $hardened ? 'public.business_today()' : 'business_today()';
        $owner = $hardened ? Rls::ROLE_DEFINER : Rls::ROLE_OWNER;
        $audit = static fn(string $table): string => sprintf(
            "RAISE EXCEPTION '%s is append-only' USING ERRCODE = 'insufficient_privilege';",
            $table,
        );

        $functions = [
            'roles_forbid_code_change' => <<<SQL
                BEGIN
                    IF NEW.code IS DISTINCT FROM OLD.code THEN
                        RAISE EXCEPTION 'The code of role % cannot be changed', OLD.code
                            USING ERRCODE = 'check_violation';
                    END IF;
                    RETURN NEW;
                END;
            SQL,
            'role_assignments_enforce_scope' => <<<SQL
                DECLARE
                    is_super boolean;
                BEGIN
                    SELECT code = 'super_administrator' INTO is_super FROM {$roles} WHERE id = NEW.role_id;
                    IF is_super AND NEW.development_id IS NOT NULL THEN
                        RAISE EXCEPTION 'The super_administrator role is global and cannot belong to a development'
                            USING ERRCODE = 'check_violation';
                    END IF;
                    IF NOT is_super AND NEW.development_id IS NULL THEN
                        RAISE EXCEPTION 'Only the super_administrator role can be assigned without a development'
                            USING ERRCODE = 'check_violation';
                    END IF;
                    RETURN NEW;
                END;
            SQL,
            'role_assignments_limit_super_administrators' => <<<SQL
                DECLARE
                    super_id bigint;
                    holders integer;
                BEGIN
                    SELECT id INTO super_id FROM {$roles} WHERE code = 'super_administrator';
                    IF NEW.role_id IS DISTINCT FROM super_id THEN
                        RETURN NEW;
                    END IF;
                    IF NEW.ends_on IS NOT NULL AND NEW.ends_on < {$today} THEN
                        RETURN NEW;
                    END IF;

                    PERFORM pg_advisory_xact_lock(hashtext('role_assignments.super_administrator'));

                    SELECT COUNT(DISTINCT user_id) INTO holders
                    FROM {$assignments}
                    WHERE role_id = super_id
                      AND user_id <> NEW.user_id
                      AND (ends_on IS NULL OR ends_on >= {$today});

                    IF holders >= 2 THEN
                        RAISE EXCEPTION 'At most 2 accounts may hold the super_administrator role'
                            USING ERRCODE = 'check_violation';
                    END IF;
                    RETURN NEW;
                END;
            SQL,
            'auth_events_reject_change' => 'BEGIN ' . $audit('auth_events') . ' END;',
            'role_assignment_events_reject_change' => 'BEGIN ' . $audit('role_assignment_events') . ' END;',
        ];

        foreach ($functions as $name => $body) {
            $this->execute(sprintf(
                'CREATE OR REPLACE FUNCTION public.%s() RETURNS trigger LANGUAGE plpgsql %s AS $fn$%s$fn$',
                $name,
                $secure,
                $body,
            ));
            if ($hardened) {
                foreach (Rls::giveToDefiner(sprintf('public.%s()', $name)) as $sql) {
                    $this->execute($sql);
                }
            } else {
                $this->execute(sprintf('ALTER FUNCTION public.%s() OWNER TO %s', $name, $owner));
            }
            if ($hardened) {
                $this->execute(sprintf('REVOKE ALL ON FUNCTION public.%s() FROM PUBLIC', $name));
            } else {
                $this->execute(sprintf('GRANT EXECUTE ON FUNCTION public.%s() TO PUBLIC', $name));
            }
        }
    }
}
