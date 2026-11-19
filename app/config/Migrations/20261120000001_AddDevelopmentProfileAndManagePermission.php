<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Profile and archive state of a development, and the permission that guards editing it (D1, D2, D3, AD6).
 *
 * - `address`, `phone`, `rfc`, `bank_name`, `bank_account`: optional text. `bank_account` is readable in SQL by every
 *   role that sees the development (coarse model, accepted in the design).
 * - `archived_at`: set when the development is archived, NULL while it is active. A development is archived, never
 *   deleted.
 * - `developments.manage` (create, edit, archive, unarchive) is seeded with NO role rows: only the
 *   super-administrator holds it, because RB-R4 resolves that role by code instead of by rows.
 *
 * Row-level security on `developments` is not touched: its policies are per row and its grant is per table, so the new
 * columns are covered, and writes stay super-administrator only. `down()` removes only what this migration adds.
 */
class AddDevelopmentProfileAndManagePermission extends BaseMigration
{
    private const PERMISSION = 'developments.manage';

    public function up(): void
    {
        $this->execute(<<<'SQL'
            ALTER TABLE developments
                ADD COLUMN address text NULL,
                ADD COLUMN phone text NULL,
                ADD COLUMN rfc text NULL,
                ADD COLUMN bank_name text NULL,
                ADD COLUMN bank_account text NULL,
                ADD COLUMN archived_at timestamp NULL
        SQL);

        $this->execute(sprintf(
            "INSERT INTO permissions (code, description) VALUES ('%s', '%s')",
            self::PERMISSION,
            'Create, edit, archive and unarchive developments',
        ));
    }

    public function down(): void
    {
        $this->execute(sprintf(
            "DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE code = '%1\$s')",
            self::PERMISSION,
        ));
        $this->execute(sprintf("DELETE FROM permissions WHERE code = '%s'", self::PERMISSION));

        $this->execute(<<<'SQL'
            ALTER TABLE developments
                DROP COLUMN archived_at,
                DROP COLUMN bank_account,
                DROP COLUMN bank_name,
                DROP COLUMN rfc,
                DROP COLUMN phone,
                DROP COLUMN address
        SQL);
    }
}
