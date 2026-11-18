<?php
declare(strict_types=1);

use App\Database\RowLevelSecurity as Rls;
use Migrations\BaseMigration;

/**
 * RLS-SEC-001, step 3: ENABLE and FORCE row-level security on every table, with one policy per command (ADR-0005
 * section 1, per-table policy table) and per-command grants to the runtime role.
 *
 * What the runtime role gets (nothing it is not listed here, and nothing on PUBLIC):
 * - developments: read within its scope; write for the super-administrator.
 * - people: read through `person_developments`; update (own row, administrators of every linked development, the
 *   super-administrator); no INSERT (person_create); DELETE for the super-administrator.
 * - person_developments, users: read only; writes go through definer functions.
 * - user_credentials, user_totp, user_backup_codes: NO privilege and no policy; definer functions only.
 * - auth_events: read own events (super-administrator: all); no write (auth_event_log).
 * - roles, permissions, role_permissions: read; the migration login writes them.
 * - role_assignments: read own rows, or those of administered developments; write where the actor administers the
 *   development (or is super); UPDATE only the columns that may change (the key columns are immutable).
 * - role_assignment_events: read and insert where the actor administers the development and the event names an
 *   assignment of that same development; never update or delete.
 *
 * Policies and grants are written for the runtime role only (`TO kualitet_app`); the migration login and the definer
 * role have BYPASSRLS.
 */
class EnableRowLevelSecurity extends BaseMigration
{
    /**
     * @var list<string> Every table the runtime role may touch in any way.
     */
    private const TABLES = [
        'developments',
        'people',
        'person_developments',
        'users',
        'user_credentials',
        'user_totp',
        'user_backup_codes',
        'auth_events',
        'roles',
        'permissions',
        'role_permissions',
        'role_assignments',
        'role_assignment_events',
    ];

    public function up(): void
    {
        $super = Rls::IS_SUPER;
        $dev = Rls::DEV_IDS;
        $admin = Rls::ADMIN_DEV_IDS;
        $me = Rls::SESSION_USER_ID;
        $person = Rls::PERSON_ID;

        foreach (self::TABLES as $table) {
            $this->run(Rls::enable($table));
            $this->execute(Rls::revokeAll($table));
        }

        // developments ---------------------------------------------------------------------------------------------
        $this->run([
            Rls::policy('developments', 'SELECT', "{$super} OR id = ANY ({$dev})"),
            Rls::policy('developments', 'INSERT', null, $super),
            Rls::policy('developments', 'UPDATE', $super, $super),
            Rls::policy('developments', 'DELETE', $super),
            Rls::grant('developments', 'SELECT, INSERT, UPDATE, DELETE'),
        ]);

        // people and person_developments -----------------------------------------------------------------------------
        $linked = "EXISTS (SELECT 1 FROM public.person_developments pd
                           WHERE pd.person_id = people.id AND pd.development_id = ANY ({$dev}))";
        $mayUpdate = "{$super} OR id = {$person} OR public.person_in_admin_scope(id)";
        $this->run([
            Rls::policy('people', 'SELECT', "id = {$person} OR {$super} OR {$linked}"),
            Rls::policy('people', 'UPDATE', $mayUpdate, $mayUpdate),
            Rls::policy('people', 'DELETE', $super),
            Rls::grant('people', 'SELECT, DELETE'),
            Rls::grant('people', 'UPDATE (name, email, phone, rfc, modified)'),
            Rls::policy('person_developments', 'SELECT', "development_id = ANY ({$dev}) OR person_id = {$person} OR {$super}"),
            Rls::grant('person_developments', 'SELECT'),
        ]);

        // users --------------------------------------------------------------------------------------------------------
        $administered = "EXISTS (SELECT 1 FROM public.role_assignments ra
                                 WHERE ra.user_id = users.id AND ra.development_id = ANY ({$admin}))";
        $this->run([
            Rls::policy('users', 'SELECT', "id = {$me} OR {$super} OR {$administered}"),
            Rls::grant('users', 'SELECT'),
        ]);

        // auth_events --------------------------------------------------------------------------------------------------
        $this->run([
            Rls::policy('auth_events', 'SELECT', "user_id = {$me} OR {$super}"),
            Rls::grant('auth_events', 'SELECT'),
        ]);

        // roles, permissions, role_permissions: readable, never writable by the runtime role ---------------------------
        foreach (['roles', 'permissions', 'role_permissions'] as $table) {
            $this->run([
                Rls::policy($table, 'SELECT', 'true'),
                Rls::grant($table, 'SELECT'),
            ]);
        }

        // role_assignments ---------------------------------------------------------------------------------------------
        $administers = "{$super} OR development_id = ANY ({$admin})";
        $this->run([
            Rls::policy('role_assignments', 'SELECT', "user_id = {$me} OR {$administers}"),
            Rls::policy('role_assignments', 'INSERT', null, $administers),
            Rls::policy('role_assignments', 'UPDATE', $administers, $administers),
            Rls::policy('role_assignments', 'DELETE', $administers),
            Rls::grant('role_assignments', 'SELECT, INSERT, DELETE'),
            Rls::grant('role_assignments', 'UPDATE (starts_on, ends_on, read_only, modified)'),
        ]);

        // role_assignment_events: append-only; the actor can only be the session user, and the event must be about an
        // assignment of the same development it claims (the subquery runs under the caller's own row visibility, so an
        // assignment the actor cannot see counts as absent) --------------------------------------------------------
        $sameDevelopment = 'EXISTS (SELECT 1 FROM public.role_assignments ra
                                    WHERE ra.id = role_assignment_events.role_assignment_id
                                      AND ra.development_id = role_assignment_events.development_id)';
        $this->run([
            Rls::policy('role_assignment_events', 'SELECT', $administers),
            Rls::policy(
                'role_assignment_events',
                'INSERT',
                null,
                "actor_user_id = {$me} AND ({$administers}) AND {$sameDevelopment}",
            ),
            Rls::grant('role_assignment_events', 'SELECT, INSERT'),
        ]);
    }

    public function down(): void
    {
        $commands = [
            'developments' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'],
            'people' => ['SELECT', 'UPDATE', 'DELETE'],
            'person_developments' => ['SELECT'],
            'users' => ['SELECT'],
            'auth_events' => ['SELECT'],
            'roles' => ['SELECT'],
            'permissions' => ['SELECT'],
            'role_permissions' => ['SELECT'],
            'role_assignments' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'],
            'role_assignment_events' => ['SELECT', 'INSERT'],
        ];
        foreach (self::TABLES as $table) {
            $this->execute(Rls::revokeAll($table));
            $this->run(Rls::dropPolicies($table, $commands[$table] ?? []));
            $this->run(Rls::disable($table));
            // Rolling back leaves a working application without row-level security: the runtime role no longer
            // owns the tables (provisioning gave them to the migration login), so it needs plain privileges.
            $this->execute(Rls::grant($table, 'SELECT, INSERT, UPDATE, DELETE'));
        }
    }

    /**
     * @param list<string> $statements SQL statements.
     * @return void
     */
    private function run(array $statements): void
    {
        foreach ($statements as $sql) {
            $this->execute($sql);
        }
    }
}
