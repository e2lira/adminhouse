<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Seeds the permission catalogue and the role/permission matrix of the fixed roles (RB-R1, RB-R2, RBAC-001).
 *
 * Permissions are defined by code, not edited by users: editable roles are RBAC-002. A permission code is
 * `<module>.<action>`; a code ending in `.view` is a read permission, the only kind a read-only role
 * assignment keeps (see App\Authorization\AccessControl).
 *
 * `super_administrator` has no rows on purpose: RB-R4 gives it every permission, including permissions added
 * by later migrations, so it is resolved by role code instead of by rows that could drift.
 *
 * The codes are the ones the spec names (`payments.create`, `reports.view`, `gate.log`) plus the modules that
 * exist or come next (developments, units, role assignments). Add a code here only together with the code that
 * checks it. The initial matrix is a first proposal; the RH-004 baseline (RB-R10) will refine it when the legacy
 * modules are rebuilt.
 */
class SeedPermissionCatalogue extends BaseMigration
{
    /**
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        'developments.view' => 'See a development and its data',
        'units.view' => 'See units (a resident is limited to their own units by a later rule)',
        'payments.create' => 'Record a payment',
        'reports.view' => 'See financial and operational reports',
        'gate.log' => 'Record access log entries at the gate',
        'role_assignments.manage' => 'Assign and end roles of users in a development',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const MATRIX = [
        'administrator' => [
            'developments.view', 'units.view', 'payments.create', 'reports.view', 'role_assignments.manage',
        ],
        'accountant' => ['developments.view', 'units.view', 'payments.create', 'reports.view'],
        'data_entry' => ['developments.view', 'units.view', 'payments.create'],
        'supervisor' => ['developments.view', 'units.view', 'reports.view'],
        // RB-R7, AC-RB-5: a guard reaches only gate functions.
        'guard' => ['gate.log'],
        // RB-R8: a resident reaches only their own units. The row-level rule for that does not exist yet, so
        // migration 20261121000003 revokes this row (AD13, user-confirmed 2026-11-18) until the resident sub-scope
        // ships in the deferred lifecycle change. This file stays as shipped so a rollback restores it.
        'resident' => ['units.view'],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $code => $description) {
            $this->execute(sprintf(
                "INSERT INTO permissions (code, description) VALUES ('%s', '%s')",
                $code,
                str_replace("'", "''", $description),
            ));
        }

        foreach (self::MATRIX as $role => $codes) {
            foreach ($codes as $code) {
                $this->execute(sprintf(
                    "INSERT INTO role_permissions (role_id, permission_id)
                     SELECT r.id, p.id FROM roles r, permissions p WHERE r.code = '%s' AND p.code = '%s'",
                    $role,
                    $code,
                ));
            }
        }
    }

    public function down(): void
    {
        $this->execute('DELETE FROM role_permissions');
        $this->execute('DELETE FROM permissions');
    }
}
