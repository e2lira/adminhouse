<?php
declare(strict_types=1);

namespace App\Test\Support;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

/**
 * Base class for tests that need the migrated PostgreSQL schema.
 *
 * Rows created by tests are removed in setUp. The seeded `roles` catalogue is
 * kept because it is part of the schema, not test data.
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

    protected function db(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');

        return $connection;
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
     * Give a user a role. Dates are offsets in days from today.
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
        $day = static fn(?int $offset): ?string => $offset === null
            ? null
            : date('Y-m-d', strtotime(sprintf('%+d days', $offset)));

        $this->db()->execute(
            'INSERT INTO role_assignments
                (user_id, role_id, development_id, starts_on, ends_on, read_only, created, modified)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $userId,
                $this->roleId($roleCode),
                $developmentId,
                $day($startsInDays),
                $day($endsInDays),
                $readOnly,
            ],
            ['integer', 'integer', 'integer', 'string', 'string', 'boolean'],
        );
    }

    protected function roleId(string $code): int
    {
        return (int)$this->db()->execute('SELECT id FROM roles WHERE code = ?', [$code])->fetch()[0];
    }
}
