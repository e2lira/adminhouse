<?php
declare(strict_types=1);

namespace App\Database;

use InvalidArgumentException;
use RuntimeException;

/**
 * Checks that a PostgreSQL extension the schema relies on is installed (design AD12).
 *
 * Extensions are created by the superuser in `bin/provision_db_roles.php`, never by a migration (the migration login
 * is not a superuser). A migration that needs one calls this first, so a database that was not provisioned fails
 * with an instruction instead of a bare "operator class does not exist".
 */
final class ExtensionGuard
{
    /**
     * @param callable(string): (array<int|string, mixed>|false|null) $fetchRow Runs a query and returns its first
     *   row, or false when there is none.
     * @param string $name Extension name, lowercase letters, digits and underscores.
     * @return void
     * @throws \RuntimeException When the extension is not installed in the current database.
     */
    public static function assertInstalled(callable $fetchRow, string $name): void
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/D', $name) !== 1) {
            throw new InvalidArgumentException('Unsafe extension name ' . $name);
        }

        $row = $fetchRow("SELECT 1 FROM pg_extension WHERE extname = '{$name}'");
        if ($row === false || $row === null || $row === []) {
            throw new RuntimeException(sprintf(
                'The PostgreSQL extension "%s" is not installed in this database. A superuser must provision it '
                . 'before this migration runs: php bin/provision_db_roles.php --database=<name> (design AD12).',
                $name,
            ));
        }
    }
}
