<?php
declare(strict_types=1);

namespace App\Database;

use Cake\Database\Connection;
use RuntimeException;

/**
 * Refuses to run the application as a role that row-level security does not constrain (ADR-0005 section 1).
 *
 * A table owner is exempt from its own policies unless they are FORCEd, and a role with BYPASSRLS or SUPERUSER ignores
 * them altogether. If `default` ever points at such a login (a copied credential, a wrong environment variable), every
 * policy silently stops protecting anything, so the first request or job of a PHP process checks the role and fails
 * fast instead. A successful check is remembered for the rest of the process.
 */
final class RuntimeRoleGuard
{
    /**
     * @var array<string, true> Connection names already verified.
     */
    private static array $verified = [];

    /**
     * @param \Cake\Database\Connection $connection The connection the application runs on.
     * @return void
     * @throws \RuntimeException When the connected role owns tables of the schema, bypasses row-level security or is a
     *  superuser.
     */
    public static function assertSafe(Connection $connection): void
    {
        if (self::isVerified($connection)) {
            return;
        }

        $row = $connection->execute(
            "SELECT r.rolname AS role, r.rolbypassrls AS bypass, r.rolsuper AS superuser,
                    EXISTS (
                        SELECT 1 FROM pg_catalog.pg_roles m
                        WHERE m.oid <> r.oid
                          AND pg_catalog.pg_has_role(r.oid, m.oid, 'USAGE')
                          AND (
                              m.rolbypassrls OR m.rolsuper
                              OR EXISTS (
                                  SELECT 1 FROM pg_catalog.pg_class c
                                  WHERE c.relowner = m.oid
                                    AND c.relnamespace = 'public'::regnamespace
                                    AND c.relkind IN ('r', 'p')
                              )
                          )
                    ) AS inherits_privileged,
                    EXISTS (
                        SELECT 1 FROM pg_catalog.pg_class c
                        WHERE c.relowner = r.oid
                          AND c.relnamespace = 'public'::regnamespace
                          AND c.relkind IN ('r', 'p')
                    ) AS owns_tables
             FROM pg_catalog.pg_roles r
             WHERE r.rolname = current_user",
        )->fetch('assoc');

        if ($row === false) {
            throw new RuntimeException('The database role of the connection could not be determined.');
        }
        if ($row['bypass'] || $row['superuser'] || $row['owns_tables'] || $row['inherits_privileged']) {
            throw new RuntimeException(sprintf(
                'The application must not run as "%s": the role owns tables, bypasses row-level security, is a '
                . 'superuser or inherits such a role. Connect `default` as the runtime role (kualitet_app).',
                $row['role'],
            ));
        }

        self::$verified[self::key($connection)] = true;
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @return bool Whether this process already verified the connection.
     */
    public static function isVerified(Connection $connection): bool
    {
        return isset(self::$verified[self::key($connection)]);
    }

    /**
     * Forget every verification (tests).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$verified = [];
    }

    /**
     * @param \Cake\Database\Connection $connection Connection.
     * @return string
     */
    private static function key(Connection $connection): string
    {
        return $connection->configName() . '@' . spl_object_id($connection);
    }
}
