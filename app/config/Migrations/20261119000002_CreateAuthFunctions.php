<?php
declare(strict_types=1);

use App\Database\RowLevelSecurity as Rls;
use Migrations\BaseMigration;

/**
 * RLS-SEC-001, step 2: the definer functions that are the ONLY way the runtime role touches credentials, second
 * factor material, the sign-in audit trail and the writes that policies do not allow directly (ADR-0005 section 1).
 *
 * All are SECURITY DEFINER, owned by the definer role, with a pinned search_path and EXECUTE for the runtime role only.
 * Sign-in steps run before any identity exists, so they take the user explicitly and return only what the step
 * needs. Functions that act for the session (password set and reset, person create, grantee lookup) derive the actor
 * from the session helpers instead. Also adds the two guards on `role_assignments` that the policies rely on.
 */
class CreateAuthFunctions extends BaseMigration
{
    /**
     * @var array<string, list<string>> Function name => parameter types, in drop order.
     */
    private const FUNCTIONS = [
        'auth_sign_in' => ['text'],
        'auth_find_active_user' => ['bigint'],
        'auth_rehash' => ['bigint', 'text', 'text'],
        'auth_set_password' => ['bigint', 'text'],
        'auth_reset' => ['bigint', 'text'],
        'auth_second_factor_required' => ['bigint', 'date'],
        'auth_totp_confirmed' => ['bigint'],
        'auth_totp_begin' => ['bigint', 'text'],
        'auth_totp_pending_secret' => ['bigint'],
        'auth_totp_confirm' => ['bigint', 'bigint'],
        'auth_totp_device' => ['bigint'],
        'auth_totp_use_step' => ['bigint', 'bigint'],
        'auth_backup_codes_replace' => ['bigint', 'text[]'],
        'auth_backup_code_consume' => ['bigint', 'text'],
        'auth_event_log' => ['bigint', 'text', 'text', 'text', 'text'],
        'user_create' => ['text', 'text', 'boolean', 'date'],
        'person_create' => ['bigint', 'text', 'text', 'text', 'text'],
        'role_assignment_grantee_id' => ['text'],
    ];

    public function up(): void
    {
        $this->defineSignIn();
        $this->definePasswords();
        $this->defineSecondFactor();
        $this->defineEvents();
        $this->defineWrites();
        $this->defineAssignmentGuards();
    }

    public function down(): void
    {
        $this->execute('DROP TRIGGER role_assignments_guard_super_grant ON role_assignments');
        $this->execute('DROP TRIGGER role_assignments_forbid_key_change ON role_assignments');
        $this->execute(Rls::dropFunction('role_assignments_guard_super_grant'));
        $this->execute(Rls::dropFunction('role_assignments_forbid_key_change'));
        foreach (array_reverse(self::FUNCTIONS, true) as $name => $types) {
            $this->execute(Rls::dropFunction($name, $types));
        }
    }

    private function defineSignIn(): void
    {
        // Sign-in: the user and its hash by username. A user that is not active is still returned: the caller
        // checks the hash first (so every outcome costs the same) and only then the status.
        $this->define('auth_sign_in', ['p_username' => 'text'], 'TABLE (id bigint, username text, status text, person_id bigint, password_hash text)', 'sql', 'STABLE', <<<'SQL'
            SELECT u.id, u.username, u.status, u.person_id, c.password_hash
            FROM public.users u
            LEFT JOIN public.user_credentials c ON c.user_id = u.id
            WHERE u.username = p_username
        SQL);

        // Reloads a signed-in user by id on every request; never returns a disabled account or a hash.
        $this->define('auth_find_active_user', ['p_id' => 'bigint'], 'TABLE (id bigint, username text, status text, person_id bigint)', 'sql', 'STABLE', <<<'SQL'
            SELECT u.id, u.username, u.status, u.person_id
            FROM public.users u
            WHERE u.id = p_id AND u.status = 'active'
        SQL);

        // Compare-and-swap, so a hash upgrade never overwrites a password changed in the meantime.
        $this->define('auth_rehash', ['p_user_id' => 'bigint', 'p_old_hash' => 'text', 'p_new_hash' => 'text'], 'boolean', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                UPDATE public.user_credentials
                SET password_hash = p_new_hash
                WHERE user_id = p_user_id AND password_hash = p_old_hash;
                RETURN FOUND;
            END;
        SQL);
    }

    private function definePasswords(): void
    {
        // A user changes their own password. The session decides who "own" is, never a parameter.
        $this->define('auth_set_password', ['p_user_id' => 'bigint', 'p_hash' => 'text'], 'boolean', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                IF p_hash IS NULL OR p_hash = '' THEN
                    RAISE EXCEPTION 'A password hash is required' USING ERRCODE = 'invalid_parameter_value';
                END IF;
                IF public.session_user_id() IS NULL OR public.session_user_id() <> p_user_id THEN
                    RAISE EXCEPTION 'A password can only be changed by its own user'
                        USING ERRCODE = 'insufficient_privilege';
                END IF;
                INSERT INTO public.user_credentials (user_id, password_hash, password_changed_at)
                VALUES (p_user_id, p_hash, pg_catalog.now())
                ON CONFLICT (user_id) DO UPDATE
                SET password_hash = EXCLUDED.password_hash, password_changed_at = EXCLUDED.password_changed_at;
                RETURN true;
            END;
        SQL);

        // A reset for somebody else (ADR-0005): a super-administrator resets anyone; an administrator only a user
        // who holds a role in at least one development they administer and in no other place, and never a
        // super-administrator. Ended and future assignments count: they still tie the user elsewhere.
        $this->define('auth_reset', ['p_target_id' => 'bigint', 'p_hash' => 'text'], 'boolean', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                IF p_hash IS NULL OR p_hash = '' THEN
                    RAISE EXCEPTION 'A password hash is required' USING ERRCODE = 'invalid_parameter_value';
                END IF;
                IF public.session_user_id() IS NULL THEN
                    RAISE EXCEPTION 'Not signed in' USING ERRCODE = 'insufficient_privilege';
                END IF;
                IF NOT public.is_super() THEN
                    IF pg_catalog.cardinality(public.admin_dev_ids()) = 0
                       OR EXISTS (
                           SELECT 1 FROM public.role_assignments ra
                           JOIN public.roles r ON r.id = ra.role_id
                           WHERE ra.user_id = p_target_id AND r.code = 'super_administrator'
                       )
                       OR NOT EXISTS (
                           SELECT 1 FROM public.role_assignments ra WHERE ra.user_id = p_target_id
                       )
                       OR EXISTS (
                           SELECT 1 FROM public.role_assignments ra
                           WHERE ra.user_id = p_target_id
                             AND (ra.development_id IS NULL OR NOT (ra.development_id = ANY (public.admin_dev_ids())))
                       )
                    THEN
                        RAISE EXCEPTION 'This password cannot be reset by the current user'
                            USING ERRCODE = 'insufficient_privilege';
                    END IF;
                END IF;
                INSERT INTO public.user_credentials (user_id, password_hash, password_changed_at)
                VALUES (p_target_id, p_hash, pg_catalog.now())
                ON CONFLICT (user_id) DO UPDATE
                SET password_hash = EXCLUDED.password_hash, password_changed_at = EXCLUDED.password_changed_at;
                RETURN true;
            END;
        SQL);
    }

    private function defineSecondFactor(): void
    {
        // Who must pass a second factor: anyone with a confirmed device, or an active super-administrator.
        $this->define('auth_second_factor_required', ['p_user_id' => 'bigint', 'p_today' => 'date'], 'boolean', 'sql', 'STABLE', <<<'SQL'
            SELECT EXISTS (
                       SELECT 1 FROM public.user_totp t WHERE t.user_id = p_user_id AND t.confirmed_at IS NOT NULL
                   )
                OR EXISTS (
                       SELECT 1
                       FROM public.role_assignments ra
                       JOIN public.roles r ON r.id = ra.role_id
                       WHERE ra.user_id = p_user_id
                         AND r.code = 'super_administrator'
                         AND ra.starts_on <= p_today
                         AND (ra.ends_on IS NULL OR ra.ends_on >= p_today)
                   )
        SQL);

        $this->define('auth_totp_confirmed', ['p_user_id' => 'bigint'], 'boolean', 'sql', 'STABLE', <<<'SQL'
            SELECT EXISTS (
                SELECT 1 FROM public.user_totp t WHERE t.user_id = p_user_id AND t.confirmed_at IS NOT NULL
            )
        SQL);

        // Starts enrolment: keeps the first secret until it is confirmed, so a code already scanned stays valid.
        $this->define('auth_totp_begin', ['p_user_id' => 'bigint', 'p_secret' => 'text'], 'text', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM public.users u WHERE u.id = p_user_id AND u.status = 'active') THEN
                    RETURN NULL;
                END IF;
                INSERT INTO public.user_totp (user_id, secret_encrypted, created)
                VALUES (p_user_id, p_secret, pg_catalog.now())
                ON CONFLICT (user_id) DO NOTHING;
                RETURN (
                    SELECT t.secret_encrypted FROM public.user_totp t
                    WHERE t.user_id = p_user_id AND t.confirmed_at IS NULL
                );
            END;
        SQL);

        // The unconfirmed secret, locked until the transaction ends so two confirmations cannot both win.
        $this->define('auth_totp_pending_secret', ['p_user_id' => 'bigint'], 'text', 'sql', 'VOLATILE', <<<'SQL'
            SELECT t.secret_encrypted
            FROM public.user_totp t
            JOIN public.users u ON u.id = t.user_id AND u.status = 'active'
            WHERE t.user_id = p_user_id AND t.confirmed_at IS NULL
            FOR UPDATE OF t
        SQL);

        $this->define('auth_totp_confirm', ['p_user_id' => 'bigint', 'p_step' => 'bigint'], 'boolean', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                UPDATE public.user_totp
                SET confirmed_at = pg_catalog.now(), last_used_step = p_step
                WHERE user_id = p_user_id AND confirmed_at IS NULL;
                RETURN FOUND;
            END;
        SQL);

        // The confirmed device of an active user, to verify a code in the application (which holds the key).
        $this->define('auth_totp_device', ['p_user_id' => 'bigint'], 'TABLE (secret_encrypted text, last_used_step bigint)', 'sql', 'STABLE', <<<'SQL'
            SELECT t.secret_encrypted, t.last_used_step
            FROM public.user_totp t
            JOIN public.users u ON u.id = t.user_id AND u.status = 'active'
            WHERE t.user_id = p_user_id AND t.confirmed_at IS NOT NULL
        SQL);

        // Replay protection: only a strictly later time step is accepted, and only once.
        $this->define('auth_totp_use_step', ['p_user_id' => 'bigint', 'p_step' => 'bigint'], 'boolean', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                UPDATE public.user_totp
                SET last_used_step = p_step
                WHERE user_id = p_user_id
                  AND confirmed_at IS NOT NULL
                  AND (last_used_step IS NULL OR last_used_step < p_step);
                RETURN FOUND;
            END;
        SQL);

        $this->define('auth_backup_codes_replace', ['p_user_id' => 'bigint', 'p_hashes' => 'text[]'], 'void', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                DELETE FROM public.user_backup_codes WHERE user_id = p_user_id;
                INSERT INTO public.user_backup_codes (user_id, code_hash, created)
                SELECT p_user_id, h, pg_catalog.now() FROM pg_catalog.unnest(p_hashes) AS h;
            END;
        SQL);

        $this->define('auth_backup_code_consume', ['p_user_id' => 'bigint', 'p_hash' => 'text'], 'boolean', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                UPDATE public.user_backup_codes
                SET used_at = pg_catalog.now()
                WHERE user_id = p_user_id AND code_hash = p_hash AND used_at IS NULL;
                RETURN FOUND;
            END;
        SQL);
    }

    private function defineEvents(): void
    {
        // The sign-in audit trail is written here, so it works before sign-in and never needs a policy; the table
        // accepts nothing else from the runtime role. The CHECK constraint on event_type rejects unknown types.
        $this->define('auth_event_log', ['p_user_id' => 'bigint', 'p_username' => 'text', 'p_type' => 'text', 'p_ip' => 'text', 'p_agent' => 'text'], 'void', 'plpgsql', 'VOLATILE', <<<'SQL'
            BEGIN
                INSERT INTO public.auth_events (user_id, username, event_type, ip, user_agent, created)
                VALUES (
                    p_user_id,
                    pg_catalog.left(p_username, 255),
                    p_type,
                    pg_catalog.left(p_ip, 255),
                    pg_catalog.left(p_agent, 255),
                    pg_catalog.now()
                );
            END;
        SQL);
    }

    private function defineWrites(): void
    {
        // Accounts are created by the `create_user` command only (the first super-administrator is a bootstrap that
        // no request can perform). The runtime role has NO EXECUTE on this function: a setting such as
        // `app.context = 'job'` is something that role can set itself, so it could not protect a function that
        // mints a super-administrator. The command calls it through the owner (`migrations`) connection, which
        // reaches it as a member of the definer role. The starting date comes from the caller: it is the business
        // day, which the command takes from BusinessClock.
        $this->define('user_create', ['p_username' => 'text', 'p_hash' => 'text', 'p_super' => 'boolean', 'p_starts_on' => 'date'], 'bigint', 'plpgsql', 'VOLATILE', <<<'SQL'
            DECLARE
                v_id bigint;
            BEGIN
                INSERT INTO public.users (username, status, created, modified)
                VALUES (p_username, 'active', pg_catalog.now(), pg_catalog.now())
                RETURNING id INTO v_id;
                INSERT INTO public.user_credentials (user_id, password_hash, password_changed_at)
                VALUES (v_id, p_hash, pg_catalog.now());
                IF p_super THEN
                    INSERT INTO public.role_assignments
                        (user_id, role_id, development_id, starts_on, read_only, created, modified)
                    SELECT v_id, r.id, NULL, p_starts_on, false, pg_catalog.now(), pg_catalog.now()
                    FROM public.roles r WHERE r.code = 'super_administrator';
                END IF;
                RETURN v_id;
            END;
        SQL, false);

        // A person and its link to the development, atomically, for an administrator of that development.
        $this->define('person_create', ['p_development_id' => 'bigint', 'p_name' => 'text', 'p_email' => 'text', 'p_phone' => 'text', 'p_rfc' => 'text'], 'bigint', 'plpgsql', 'VOLATILE', <<<'SQL'
            DECLARE
                v_id bigint;
            BEGIN
                IF NOT (
                    public.is_super()
                    OR (p_development_id IS NOT NULL AND p_development_id = ANY (public.admin_dev_ids()))
                ) THEN
                    RAISE EXCEPTION 'Not allowed to create people in this development'
                        USING ERRCODE = 'insufficient_privilege';
                END IF;
                INSERT INTO public.people (name, email, phone, rfc, created, modified)
                VALUES (p_name, p_email, p_phone, p_rfc, pg_catalog.now(), pg_catalog.now())
                RETURNING id INTO v_id;
                INSERT INTO public.person_developments (person_id, development_id) VALUES (v_id, p_development_id);
                RETURN v_id;
            END;
        SQL);

        // Granting a role names the grantee by username, but an administrator cannot see users who hold no role in
        // their developments yet. This returns the id of an ACTIVE user, only to someone who can grant roles.
        $this->define('role_assignment_grantee_id', ['p_username' => 'text'], 'bigint', 'plpgsql', 'STABLE', <<<'SQL'
            BEGIN
                IF NOT (public.is_super() OR pg_catalog.cardinality(public.admin_dev_ids()) > 0) THEN
                    RETURN NULL;
                END IF;
                RETURN (
                    SELECT u.id FROM public.users u WHERE u.username = p_username AND u.status = 'active'
                );
            END;
        SQL);
    }

    private function defineAssignmentGuards(): void
    {
        // The key of an assignment never changes: revoke it and create a new one instead (ADR-0005). Applies to
        // every role, the migration login included.
        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.role_assignments_forbid_key_change() RETURNS trigger
            LANGUAGE plpgsql SET search_path = pg_catalog, pg_temp AS $$
            BEGIN
                IF NEW.user_id IS DISTINCT FROM OLD.user_id
                   OR NEW.role_id IS DISTINCT FROM OLD.role_id
                   OR NEW.development_id IS DISTINCT FROM OLD.development_id THEN
                    RAISE EXCEPTION 'The user, role and development of an assignment cannot be changed; end it and grant a new one'
                        USING ERRCODE = 'check_violation';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);

        // Only a super-administrator grants the global role. SECURITY INVOKER on purpose: it must see WHO runs the
        // statement. The definer role (user_create, for the bootstrap) and the migration login are the exceptions.
        $this->execute(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.role_assignments_guard_super_grant() RETURNS trigger
            LANGUAGE plpgsql SET search_path = pg_catalog, pg_temp AS $$
            BEGIN
                IF NEW.role_id = (SELECT r.id FROM public.roles r WHERE r.code = 'super_administrator')
                   AND current_user NOT IN ('kualitet_owner', 'kualitet_definer')
                   AND NOT public.is_super() THEN
                    RAISE EXCEPTION 'Only a super-administrator can grant the super_administrator role'
                        USING ERRCODE = 'insufficient_privilege';
                END IF;
                RETURN NEW;
            END;
            $$
        SQL);
        foreach (['role_assignments_forbid_key_change', 'role_assignments_guard_super_grant'] as $function) {
            foreach (Rls::giveToDefiner(sprintf('public.%s()', $function)) as $sql) {
                $this->execute($sql);
            }
            $this->execute(sprintf('REVOKE ALL ON FUNCTION public.%s() FROM PUBLIC', $function));
        }

        $this->execute(<<<'SQL'
            CREATE TRIGGER role_assignments_forbid_key_change
            BEFORE UPDATE ON role_assignments
            FOR EACH ROW EXECUTE FUNCTION public.role_assignments_forbid_key_change()
        SQL);
        $this->execute(<<<'SQL'
            CREATE TRIGGER role_assignments_guard_super_grant
            BEFORE INSERT OR UPDATE ON role_assignments
            FOR EACH ROW EXECUTE FUNCTION public.role_assignments_guard_super_grant()
        SQL);
    }

    /**
     * @param string $name Function name.
     * @param array<string, string> $parameters Parameters.
     * @param string $returns Return type.
     * @param string $language Language.
     * @param string $volatility Volatility.
     * @param string $body Body.
     * @param bool $callableByApp Whether the runtime role may execute it.
     * @return void
     */
    private function define(
        string $name,
        array $parameters,
        string $returns,
        string $language,
        string $volatility,
        string $body,
        bool $callableByApp = true,
    ): void {
        foreach (Rls::definerFunction($name, $parameters, $returns, $language, $volatility, $body, $callableByApp) as $sql) {
            $this->execute($sql);
        }
    }
}
