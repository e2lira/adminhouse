<?php
declare(strict_types=1);

use App\Database\RowLevelSecurity as Rls;
use Migrations\BaseMigration;

/**
 * People follow the permission catalogue at the database level (AD8, S-3, S-4).
 *
 * In this order (S-4: a permission is seeded before anything checks it):
 * 1. seeds `people.view` (administrator, accountant, data_entry, supervisor; never resident, AD13) and
 *    `people.manage` (administrator, data_entry);
 * 2. adds `person_in_permission_scope(person, code)`: true only when the person has at least one development link and
 *    EVERY linked development is in `permission_dev_ids(code)` (evaluated once per call, not once per link). For a write code an archived development drops out
 *    of that list, so a person shared with an archived development cannot be edited (S-3);
 * 3. replaces the body of `person_create` so it checks `permission_dev_ids('people.manage')` instead of
 *    `admin_dev_ids()`. The error is the same for a development the caller does not manage and one that does not
 *    exist, so the call is not an existence oracle;
 * 4. replaces the people UPDATE policy: `is_super OR own row OR person_in_permission_scope(id, 'people.manage')`.
 *    The SELECT policy is unchanged (it still follows `dev_ids()`).
 *
 * `down()` undoes exactly this, in reverse: it restores the previous policy and the previous `person_create` body
 * (both from 20261119000002/3), drops the new function and deletes only the rows and codes seeded here.
 *
 * Both functions are SECURITY DEFINER, owned by the definer role, with a pinned search_path and EXECUTE for the
 * runtime role only. Re-running `bin/provision_db_roles.php` is not needed.
 */
class PeoplePermissionScope extends BaseMigration
{
    private const VIEW = 'people.view';

    private const MANAGE = 'people.manage';

    private const GRANTS = [
        self::VIEW => ['administrator', 'accountant', 'data_entry', 'supervisor'],
        self::MANAGE => ['administrator', 'data_entry'],
    ];

    private const DESCRIPTIONS = [
        self::VIEW => 'See the people of a development',
        self::MANAGE => 'Create and edit the people of a development',
    ];

    /**
     * @return void
     */
    public function up(): void
    {
        foreach (self::GRANTS as $code => $roles) {
            $this->execute(sprintf(
                "INSERT INTO permissions (code, description) VALUES ('%s', '%s')",
                $code,
                self::DESCRIPTIONS[$code],
            ));
            foreach ($roles as $role) {
                $this->execute(sprintf(
                    "INSERT INTO role_permissions (role_id, permission_id)
                     SELECT r.id, p.id FROM roles r, permissions p WHERE r.code = '%s' AND p.code = '%s'",
                    $role,
                    $code,
                ));
            }
        }

        $scopeParameters = ['p_person_id' => 'bigint', 'p_code' => 'text'];
        $this->define('person_in_permission_scope', $scopeParameters, 'boolean', 'sql', 'STABLE', <<<'SQL'
            SELECT EXISTS (
                       SELECT 1 FROM public.person_developments pd WHERE pd.person_id = p_person_id
                   )
               AND NOT EXISTS (
                       SELECT 1
                       FROM (SELECT public.permission_dev_ids(p_code) AS ids) scope
                       JOIN public.person_developments pd ON pd.person_id = p_person_id
                       WHERE pd.development_id <> ALL (scope.ids)
                   )
        SQL);

        $this->definePersonCreate("p_development_id = ANY (public.permission_dev_ids('people.manage'))");

        $this->execute('DROP POLICY people_update ON people');
        $mayUpdate = Rls::IS_SUPER . ' OR id = ' . Rls::PERSON_ID
            . " OR public.person_in_permission_scope(id, 'people.manage')";
        $this->execute(Rls::policy('people', 'UPDATE', $mayUpdate, $mayUpdate));
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute('DROP POLICY people_update ON people');
        $mayUpdate = Rls::IS_SUPER . ' OR id = ' . Rls::PERSON_ID . ' OR public.person_in_admin_scope(id)';
        $this->execute(Rls::policy('people', 'UPDATE', $mayUpdate, $mayUpdate));

        $this->definePersonCreate('p_development_id = ANY (public.admin_dev_ids())');
        $this->execute(Rls::dropFunction('person_in_permission_scope', ['bigint', 'text']));

        foreach (array_keys(self::GRANTS) as $code) {
            $this->execute(sprintf(
                "DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE code = '%s')",
                $code,
            ));
            $this->execute(sprintf("DELETE FROM permissions WHERE code = '%s'", $code));
        }
    }

    /**
     * A person and its link to the development, atomically. Same signature as the original (20261119000002); only the
     * development check differs.
     *
     * @param string $allowed Predicate on `p_development_id` that holds for a development the session may write to.
     * @return void
     */
    private function definePersonCreate(string $allowed): void
    {
        $parameters = [
            'p_development_id' => 'bigint', 'p_name' => 'text', 'p_email' => 'text', 'p_phone' => 'text',
            'p_rfc' => 'text',
        ];
        $this->define('person_create', $parameters, 'bigint', 'plpgsql', 'VOLATILE', <<<SQL
            DECLARE
                v_id bigint;
            BEGIN
                IF NOT (
                    public.is_super()
                    OR (p_development_id IS NOT NULL AND {$allowed})
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
    }

    /**
     * @param string $name Function name.
     * @param array<string, string> $parameters Parameter name => type.
     * @param string $returns Return type.
     * @param string $language `sql` or `plpgsql`.
     * @param string $volatility `STABLE` or `VOLATILE`.
     * @param string $body Function body.
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
}
