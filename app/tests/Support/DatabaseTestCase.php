<?php
declare(strict_types=1);

namespace App\Test\Support;

use App\Database\DevelopmentContext;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDOException;
use Throwable;

/**
 * Base class for tests that need the migrated PostgreSQL schema.
 *
 * Two connections are in play (RLS-SEC-001):
 * - `db()` is the migration/owner login. It bypasses row-level security and is meant for fixtures and for
 *   assertions about stored state, never for the code under test.
 * - `appDb()` is the runtime role the application runs as: it owns nothing and every policy applies to it. The code
 *   under test uses it (through the `default` connection). Call actAs() or actAsJob() first to give it the session
 *   values a request or a job would have.
 *
 * Rows created by tests are removed in setUp. The seeded `roles` catalogue is kept because it is part of the
 * schema, not test data.
 */
abstract class DatabaseTestCase extends TestCase
{
    /**
     * Tables emptied before each test. Cascading covers dependent tables.
     *
     * @var list<string>
     */
    protected const TEST_DATA_TABLES = [
        'auth_events',
        'role_assignment_events',
        'role_assignments',
        'user_credentials',
        'users',
        'person_developments',
        'people',
        'developments',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Never truncate anything but a database that is explicitly a test database.
        $database = (string)($this->db()->config()['database'] ?? '');
        if (!str_contains($database, 'test')) {
            $this->fail(sprintf('Refusing to truncate `%s`: the test database name must contain "test".', $database));
        }

        $this->db()->execute(
            'TRUNCATE ' . implode(', ', self::TEST_DATA_TABLES) . ' RESTART IDENTITY CASCADE',
        );
    }

    protected function tearDown(): void
    {
        // A test that opened a session (actAs) must not leak its transaction into the next one.
        $connection = $this->appDb();
        while ($connection->inTransaction()) {
            $connection->rollback();
        }

        parent::tearDown();
    }

    /**
     * The migration login: owns the tables and bypasses row-level security. For fixtures and assertions only.
     *
     * @return \Cake\Database\Connection
     */
    protected function db(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('migrations');

        return $connection;
    }

    /**
     * The runtime role, as the application connects (`default`, aliased to `test` in the suite).
     *
     * @return \Cake\Database\Connection
     */
    protected function appDb(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');

        return $connection;
    }

    /**
     * Run a statement with the owner login and report how the database answered.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Positional parameters.
     * @return string|null SQLSTATE of the error, or null when the statement succeeded.
     */
    protected function ownerSqlState(string $sql, array $params = []): ?string
    {
        try {
            $this->db()->execute($sql, $params)->fetchAll();

            return null;
        } catch (Throwable $e) {
            for (; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                    return (string)$e->errorInfo[0];
                }
            }

            return 'unknown';
        }
    }

    /**
     * Start a request-like session on the runtime connection: one open transaction with `app.context = 'request'`
     * and `app.user_id` set (null leaves it empty, as before sign-in). Rolled back in tearDown.
     *
     * @param int|null $userId Signed-in user, or null for an anonymous request.
     * @return void
     */
    protected function actAs(?int $userId): void
    {
        $context = new DevelopmentContext($this->appDb());
        $context->beginRequest($userId);
    }

    /**
     * Start a job-like session: `app.context = 'job'` and the developments the job may touch.
     *
     * @param list<int> $developmentIds Developments of the job.
     * @return void
     */
    protected function actAsJob(array $developmentIds): void
    {
        (new DevelopmentContext($this->appDb()))->beginJob($developmentIds);
    }

    /**
     * Commit the session opened by actAs() or actAsJob(), so the owner connection can see what it wrote.
     *
     * @return void
     */
    protected function commitSession(): void
    {
        (new DevelopmentContext($this->appDb()))->commit();
    }

    /**
     * End the session opened by actAs() or actAsJob() without keeping its changes.
     *
     * @return void
     */
    protected function endSession(): void
    {
        (new DevelopmentContext($this->appDb()))->rollback();
    }

    /**
     * Insert a user (and optionally its credentials) with plain SQL.
     *
     * @param string $username Username.
     * @param string|null $password Plain password, null for a user without credentials.
     * @param string $status User status.
     * @return int New user id.
     */
    protected function createUser(
        string $username,
        ?string $password = 'correct horse battery',
        string $status = 'active',
    ): int {
        $this->db()->execute(
            'INSERT INTO users (username, status, created, modified) VALUES (?, ?, NOW(), NOW())',
            [$username, $status],
        );
        $id = (int)$this->db()->execute('SELECT id FROM users WHERE username = ?', [$username])->fetch()[0];

        if ($password !== null) {
            $this->db()->execute(
                'INSERT INTO user_credentials (user_id, password_hash, password_changed_at) VALUES (?, ?, NOW())',
                [$id, (new DefaultPasswordHasher())->hash($password)],
            );
        }

        return $id;
    }

    protected function createDevelopment(string $name = 'Residencial Demo'): int
    {
        $this->db()->execute(
            "INSERT INTO developments (type, name, section_label, number_label, created, modified)
             VALUES ('condominium', ?, 'Torre', 'Departamento', NOW(), NOW())",
            [$name],
        );

        return (int)$this->db()->execute('SELECT id FROM developments WHERE name = ?', [$name])->fetch()[0];
    }

    /**
     * A calendar date relative to today in the business time zone, taken from the database's own `business_today()`
     * (the clock row-level security uses), so a fixture never straddles midnight between the PHP and database
     * clocks and never flakes between 18:00 and 24:00 local time, when UTC is already on the next day.
     *
     * @param int $offset Days from today (negative for the past).
     * @return string `Y-m-d`.
     */
    protected function businessDay(int $offset = 0): string
    {
        return (string)$this->db()->execute('SELECT (public.business_today() + ?::int)::text', [$offset])->fetch()[0];
    }

    /**
     * Give a user a role. Dates are offsets in days from today in the business time zone.
     *
     * @param int $userId User id.
     * @param string $roleCode Role code.
     * @param int|null $developmentId Development id; null only for super_administrator.
     * @param int $startsInDays Offset of `starts_on` from today.
     * @param int|null $endsInDays Offset of `ends_on` from today, or null for an open assignment.
     * @param bool $readOnly Whether the assignment is read-only.
     * @return void
     */
    protected function assignRole(
        int $userId,
        string $roleCode,
        ?int $developmentId,
        int $startsInDays = -30,
        ?int $endsInDays = null,
        bool $readOnly = false,
    ): void {
        $this->assignRoleOn(
            $userId,
            $roleCode,
            $developmentId,
            $this->businessDay($startsInDays),
            $endsInDays === null ? null : $this->businessDay($endsInDays),
            $readOnly,
        );
    }

    /**
     * Give a user a role with explicit dates.
     *
     * @param int $userId User id.
     * @param string $roleCode Role code.
     * @param int|null $developmentId Development id; null only for super_administrator.
     * @param string $startsOn `Y-m-d`.
     * @param string|null $endsOn `Y-m-d`, or null for an open assignment.
     * @param bool $readOnly Whether the assignment is read-only.
     * @return void
     */
    protected function assignRoleOn(
        int $userId,
        string $roleCode,
        ?int $developmentId,
        string $startsOn,
        ?string $endsOn = null,
        bool $readOnly = false,
    ): void {
        $this->db()->execute(
            'INSERT INTO role_assignments
                (user_id, role_id, development_id, starts_on, ends_on, read_only, created, modified)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [$userId, $this->roleId($roleCode), $developmentId, $startsOn, $endsOn, $readOnly],
            ['integer', 'integer', 'integer', 'string', 'string', 'boolean'],
        );
    }

    protected function roleId(string $code): int
    {
        return (int)$this->db()->execute('SELECT id FROM roles WHERE code = ?', [$code])->fetch()[0];
    }
}
