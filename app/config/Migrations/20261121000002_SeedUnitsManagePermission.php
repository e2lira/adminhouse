<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Seeds `units.manage` and gives it to the roles that edit the units of a development (AD6, D6).
 *
 * The permission is seeded in the slice that first checks it. `administrator` and `data_entry` hold it;
 * `super_administrator` needs no rows (RB-R4). Writes to units are further limited to unarchived developments by
 * `DevelopmentPolicy::canManageUnits` (layer 1) and by `permission_dev_ids` (layer 2).
 *
 * Unlike the catalogue seed (20261118000001), `down()` removes only the rows this migration adds.
 */
class SeedUnitsManagePermission extends BaseMigration
{
    private const PERMISSION = 'units.manage';

    private const ROLES = ['administrator', 'data_entry'];

    /**
     * @return void
     */
    public function up(): void
    {
        $this->execute(sprintf(
            "INSERT INTO permissions (code, description) VALUES ('%s', '%s')",
            self::PERMISSION,
            'Create, edit and deactivate the units and sections of a development',
        ));

        foreach (self::ROLES as $role) {
            $this->execute(sprintf(
                "INSERT INTO role_permissions (role_id, permission_id)
                 SELECT r.id, p.id FROM roles r, permissions p WHERE r.code = '%s' AND p.code = '%s'",
                $role,
                self::PERMISSION,
            ));
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute(sprintf(
            "DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE code = '%s')",
            self::PERMISSION,
        ));
        $this->execute(sprintf("DELETE FROM permissions WHERE code = '%s'", self::PERMISSION));
    }
}
