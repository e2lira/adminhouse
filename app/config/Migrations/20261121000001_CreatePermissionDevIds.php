<?php
declare(strict_types=1);

use App\Database\RowLevelSecurity as Rls;
use Migrations\BaseMigration;

/**
 * Layer-2 helper that makes database write rights follow the permission catalogue (AD7, ADR-0005 section 1).
 *
 * `permission_dev_ids(p_code)` lists the developments where the session user may exercise a permission:
 * - request context only: it is empty in jobs and webhooks, so a job stays read-only on tables that use it;
 * - an active assignment today (`starts_on <= today`, `ends_on` NULL or `>= today`), on an active user, whose role
 *   holds the code in `role_permissions`;
 * - a code that does not end in `.view` also needs a writable assignment (`read_only` is false) and a development
 *   that is not archived (W-E). This is the one documented divergence from `AccessControl::allowedFor`, which
 *   includes archived developments: layer 1 refuses them through `DevelopmentPolicy::canManage*`, layer 2 here.
 * - `super_administrator` has no development rows, so it is not listed; policies add `is_super()` next to it.
 *
 * The function is STABLE SECURITY DEFINER, owned by the definer role, with a pinned search_path and EXECUTE for the
 * runtime role only. `down()` drops it and nothing else.
 */
class CreatePermissionDevIds extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $today = 'public.business_today()';
        $body = <<<SQL
            SELECT COALESCE((
                SELECT pg_catalog.array_agg(DISTINCT ra.development_id)
                FROM public.role_assignments ra
                JOIN public.roles r ON r.id = ra.role_id
                JOIN public.developments d ON d.id = ra.development_id
                WHERE pg_catalog.current_setting('app.context', true) = 'request'
                  AND ra.user_id = public.session_user_id()
                  AND ra.starts_on <= {$today}
                  AND (ra.ends_on IS NULL OR ra.ends_on >= {$today})
                  AND (pg_catalog.right(p_code, 5) = '.view' OR (NOT ra.read_only AND d.archived_at IS NULL))
                  AND EXISTS (
                      SELECT 1
                      FROM public.role_permissions rp
                      JOIN public.permissions p ON p.id = rp.permission_id
                      WHERE rp.role_id = r.id AND p.code = p_code
                  )
            ), ARRAY[]::bigint[])
        SQL;

        $function = ['p_code' => 'text'];
        foreach (Rls::definerFunction('permission_dev_ids', $function, 'bigint[]', 'sql', 'STABLE', $body) as $sql) {
            $this->execute($sql);
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute(Rls::dropFunction('permission_dev_ids', ['text']));
    }
}
