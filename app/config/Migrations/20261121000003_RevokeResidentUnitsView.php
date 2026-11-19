<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * A resident loses `units.view` (AD13, user-confirmed 2026-11-18).
 *
 * The catalogue seed (20261118000001) gave the resident `units.view` on the promise that a row-level rule would limit
 * them to their own units. That rule does not exist yet, so the permission would let a resident read every unit of
 * the development (RB-R8). It is revoked until the resident sub-scope ships in the deferred lifecycle change (D7).
 *
 * `down()` re-grants exactly that one row and touches nothing else, so a rollback restores the previous matrix.
 */
class RevokeResidentUnitsView extends BaseMigration
{
    private const ROLE = 'resident';

    private const PERMISSION = 'units.view';

    /**
     * @return void
     */
    public function up(): void
    {
        $this->execute(sprintf(
            "DELETE FROM role_permissions
             WHERE role_id = (SELECT id FROM roles WHERE code = '%s')
               AND permission_id = (SELECT id FROM permissions WHERE code = '%s')",
            self::ROLE,
            self::PERMISSION,
        ));
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute(sprintf(
            "INSERT INTO role_permissions (role_id, permission_id)
             SELECT r.id, p.id FROM roles r, permissions p WHERE r.code = '%s' AND p.code = '%s'
             ON CONFLICT (role_id, permission_id) DO NOTHING",
            self::ROLE,
            self::PERMISSION,
        ));
    }
}
