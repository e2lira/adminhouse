<?php
declare(strict_types=1);

/**
 * Provisions the PostgreSQL roles of the row-level security design (ADR-0005 section 1, RLS-SEC-001).
 *
 * Run it ONCE per cluster and database as a PostgreSQL superuser, before `migrations migrate -c migrations`. It
 * is idempotent: running it again changes nothing that is already right. Passwords are never printed.
 *
 * Roles:
 * - kualitet_app     application runtime login. Owns nothing, no BYPASSRLS. Used by `default` and `test`.
 * - kualitet_owner   migration login. Owns every table, BYPASSRLS. Used only by the `migrations` and
 *                    `test_migrations` connections (migrations and the `create_user` bootstrap command). Created with
 *                    LOGIN. In production set it NOLOGIN after the migration window (`ALTER ROLE kualitet_owner
 *                    NOLOGIN`). Re-running this script KEEPS that state: it never turns LOGIN back on by itself; pass
 *                    --owner-login to do so before the next migration window.
 * - kualitet_definer NOLOGIN, BYPASSRLS. Owns every SECURITY DEFINER function and trigger function.
 *
 * Extensions: `btree_gist` is created in every provisioned database (the occupancies migration only checks for it, AD12).
 * Re-run this script before migrating a database that predates the occupancies change.
 *
 * Usage (superuser from the PG* environment variables or ~/.pgpass):
 *   php bin/provision_db_roles.php [--database=NAME ...] [--write-local] [--owner-login]
 *
 * Without --database the databases named by the `default` and `test` datasources of config/app_local.php are used.
 * --write-local generates a random password for kualitet_owner when none is configured and adds the `migrations`
 * and `test_migrations` datasources to the git-ignored config/app_local.php. --owner-login makes sure kualitet_owner
 * can log in (without it an existing role keeps its current LOGIN/NOLOGIN state).
 *
 * Environment:
 *   PGHOST, PGPORT, PGUSER, PGPASSWORD   superuser connection (defaults localhost, 5432, postgres, ~/.pgpass)
 *   KUALITET_APP_PASSWORD                password of kualitet_app (default: Datasources.default.password)
 *   KUALITET_OWNER_PASSWORD              password of kualitet_owner (default: Datasources.migrations.password)
 */

const ROLE_APP = 'kualitet_app';
const ROLE_OWNER = 'kualitet_owner';
const ROLE_DEFINER = 'kualitet_definer';

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @return array<string, mixed> The configuration of config/app_local.php, or an empty array.
 */
function localConfig(): array
{
    $file = dirname(__DIR__) . '/config/app_local.php';

    return is_file($file) ? (array)(include $file) : [];
}

/**
 * @param string $dsn DSN.
 * @param string $user User.
 * @param string|null $password Password, null to let libpq read ~/.pgpass.
 * @return \PDO
 */
function connect(string $dsn, string $user, ?string $password): PDO
{
    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

/**
 * @param string $name Identifier.
 * @return string
 */
function ident(string $name): string
{
    return '"' . str_replace('"', '""', $name) . '"';
}

/**
 * Create the role or bring its attributes (and password, when known) to the wanted state.
 *
 * @param \PDO $pdo Superuser connection.
 * @param string $role Role name.
 * @param string $attributes Attribute list, for example `LOGIN NOBYPASSRLS`.
 * @param string|null $password Password, or null to leave it as it is.
 * @return void
 */
function ensureRole(PDO $pdo, string $role, string $attributes, ?string $password): void
{
    $exists = $pdo->query('SELECT 1 FROM pg_roles WHERE rolname = ' . $pdo->quote($role))->fetchColumn() !== false;
    $secret = $password === null ? '' : ' PASSWORD ' . $pdo->quote($password);
    $verb = $exists ? 'ALTER ROLE' : 'CREATE ROLE';
    if (!$exists && $password === null && preg_match('/(?<!NO)LOGIN/', $attributes) === 1) {
        fwrite(STDERR, sprintf(
            "WARNING: role %s is created WITHOUT a password. Set KUALITET_APP_PASSWORD (or Datasources.default.password)"
            . " and run this script again before using it outside a trusted local setup.
",
            $role,
        ));
    }
    $pdo->exec(sprintf(
        '%s %s WITH %s NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION%s',
        $verb,
        ident($role),
        $attributes,
        $secret,
    ));
    fwrite(STDOUT, sprintf("%s role %s (%s)\n", $exists ? 'Updated' : 'Created', $role, $attributes));
}

/**
 * @param string $text app_local.php source.
 * @param array<string, mixed> $default `default` datasource.
 * @param array<string, mixed> $test `test` datasource.
 * @param string $password Owner password.
 * @return string
 */
function withMigrationDatasources(string $text, array $default, array $test, string $password): string
{
    $block = static function (string $name, array $source, string $envUrl) use ($password): string {
        return sprintf(
            "        '%s' => [\n"
            . "            'host' => %s,\n"
            . "            'port' => %s,\n"
            . "            'username' => '%s',\n"
            . "            'password' => '%s',\n"
            . "            'database' => %s,\n"
            . "            'url' => env('%s', null),\n"
            . "        ],\n",
            $name,
            var_export((string)($source['host'] ?? '127.0.0.1'), true),
            var_export((int)($source['port'] ?? 5432), true),
            ROLE_OWNER,
            $password,
            var_export((string)($source['database'] ?? ''), true),
            $envUrl,
        );
    };
    $insert = "        // Migration login (RLS-SEC-001): owns the tables and bypasses row-level security. Only the\n"
        . "        // `migrations` and `test_migrations` connections use it; the application runs as kualitet_app.\n"
        . $block('migrations', $default, 'DATABASE_MIGRATIONS_URL')
        . $block('test_migrations', $test, 'DATABASE_TEST_MIGRATIONS_URL');

    $count = 0;
    $result = preg_replace('/(\'Datasources\' => \[\R)/', '$1' . addcslashes($insert, '\\$'), $text, 1, $count);
    if ($result === null || $count !== 1) {
        throw new RuntimeException('Could not find the Datasources block in app_local.php.');
    }

    return $result;
}

$options = getopt('', ['database::', 'write-local', 'owner-login']) ?: [];
$writeLocal = array_key_exists('write-local', $options);
$ownerLogin = array_key_exists('owner-login', $options);
$config = localConfig();
$sources = $config['Datasources'] ?? [];

$databases = [];
foreach ((array)($options['database'] ?? []) as $name) {
    $databases[] = (string)$name;
}
if ($databases === []) {
    foreach (['default', 'test'] as $key) {
        if (!empty($sources[$key]['database'])) {
            $databases[] = (string)$sources[$key]['database'];
        }
    }
}
$databases = array_values(array_unique($databases));
if ($databases === []) {
    fwrite(STDERR, "No database to provision: pass --database=NAME or configure config/app_local.php.\n");
    exit(1);
}

$appPassword = getenv('KUALITET_APP_PASSWORD') ?: ($sources['default']['password'] ?? null);
$ownerPassword = getenv('KUALITET_OWNER_PASSWORD') ?: ($sources['migrations']['password'] ?? null);
$generated = false;
if ($ownerPassword === null || $ownerPassword === '') {
    if (!$writeLocal) {
        fwrite(STDERR, "No password for kualitet_owner: set KUALITET_OWNER_PASSWORD or pass --write-local.\n");
        exit(1);
    }
    $ownerPassword = bin2hex(random_bytes(24));
    $generated = true;
}

$host = getenv('PGHOST') ?: 'localhost';
$port = getenv('PGPORT') ?: '5432';
$user = getenv('PGUSER') ?: 'postgres';
$superPassword = getenv('PGPASSWORD') ?: null;
$dsn = static fn(string $database): string => sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);

// Roles are cluster-wide: create them once, from the first database.
$pdo = connect($dsn($databases[0]), $user, $superPassword);
$isSuper = $pdo->query("SELECT rolsuper FROM pg_roles WHERE rolname = current_user")->fetchColumn();
if (!$isSuper) {
    fwrite(STDERR, "This script must run as a PostgreSQL superuser.\n");
    exit(1);
}

ensureRole($pdo, ROLE_DEFINER, 'NOLOGIN BYPASSRLS', null);
// LOGIN only when the role is new or --owner-login is given: a role the operator set NOLOGIN stays that way.
$ownerExists = $pdo->query('SELECT 1 FROM pg_roles WHERE rolname = ' . $pdo->quote(ROLE_OWNER))->fetchColumn() !== false;
ensureRole($pdo, ROLE_OWNER, ($ownerLogin || !$ownerExists ? 'LOGIN ' : '') . 'BYPASSRLS', (string)$ownerPassword);
ensureRole(
    $pdo,
    ROLE_APP,
    'LOGIN NOBYPASSRLS',
    is_string($appPassword) && $appPassword !== '' ? $appPassword : null,
);
// The migration login must be able to hand function ownership to the definer role (ALTER FUNCTION ... OWNER TO).
$pdo->exec('GRANT ' . ident(ROLE_DEFINER) . ' TO ' . ident(ROLE_OWNER));

foreach ($databases as $database) {
    $db = connect($dsn($database), $user, $superPassword);
    $dbName = ident($database);

    $db->exec("ALTER DATABASE {$dbName} OWNER TO " . ident(ROLE_OWNER));
    $db->exec("REVOKE ALL ON DATABASE {$dbName} FROM PUBLIC");
    $db->exec("GRANT CONNECT ON DATABASE {$dbName} TO " . ident(ROLE_APP) . ', ' . ident(ROLE_OWNER));

    // Objects created before this design belong to the runtime role: hand them to the migration login so the
    // runtime role owns nothing and row-level security applies to it.
    $db->exec('REASSIGN OWNED BY ' . ident(ROLE_APP) . ' TO ' . ident(ROLE_OWNER));

    $db->exec('ALTER SCHEMA public OWNER TO ' . ident(ROLE_OWNER));
    $db->exec('REVOKE ALL ON SCHEMA public FROM PUBLIC');
    $db->exec('GRANT USAGE ON SCHEMA public TO ' . ident(ROLE_APP) . ', ' . ident(ROLE_DEFINER));
    // The definer role gets no standing CREATE on the schema. `ALTER FUNCTION ... OWNER TO` needs it, so the
    // migrations grant it for those statements and revoke it right after (RowLevelSecurity::giveToDefiner).
    // Revoked here as well so a database provisioned by an older version converges.
    $db->exec('REVOKE CREATE ON SCHEMA public FROM ' . ident(ROLE_DEFINER));

    // Extensions need a superuser, so provisioning owns them and the migrations only check (design AD12).
    // btree_gist lets the occupancies exclusion constraints compare bigint and text with `=` inside a gist index.
    $db->exec('CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA public');

    // A function created later by either role is not executable by PUBLIC unless a migration says so.
    foreach ([ROLE_OWNER, ROLE_DEFINER] as $creator) {
        $db->exec('ALTER DEFAULT PRIVILEGES FOR ROLE ' . ident($creator) . ' REVOKE EXECUTE ON FUNCTIONS FROM PUBLIC');
    }

    fwrite(STDOUT, sprintf("Provisioned database %s\n", $database));
}

if ($generated) {
    $file = dirname(__DIR__) . '/config/app_local.php';
    if (!is_file($file)) {
        fwrite(STDERR, "config/app_local.php does not exist; copy app_local.example.php first.\n");
        exit(1);
    }
    $updated = withMigrationDatasources(
        (string)file_get_contents($file),
        (array)($sources['default'] ?? []),
        (array)($sources['test'] ?? []),
        (string)$ownerPassword,
    );
    file_put_contents($file, $updated);
    fwrite(STDOUT, "Wrote the migrations and test_migrations datasources to config/app_local.php.\n");
}

fwrite(STDOUT, "Done. Run migrations with: bin/cake migrations migrate -c migrations\n");
