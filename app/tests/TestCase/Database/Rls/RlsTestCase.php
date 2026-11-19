<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database\Rls;

use App\Test\Support\DatabaseTestCase;
use PDOException;
use Throwable;

/**
 * Scenario shared by the row-level security policy tests (RLS-SEC-001, ADR-0005 section 1).
 *
 * Two developments, and one user for each situation a policy must tell apart. Fixtures are written with the owner
 * connection; every assertion about access runs as the runtime role through appDb(), after actAs() / actAsJob().
 */
abstract class RlsTestCase extends DatabaseTestCase
{
    protected int $devA;

    protected int $devB;

    protected int $superUser;

    protected int $adminA;

    protected int $adminB;

    protected int $accountantA;

    protected int $dataEntryA;

    protected int $guardA;

    protected int $resident;

    protected int $disabledAdmin;

    protected int $endedAdmin;

    protected int $futureAdmin;

    protected int $loner;

    protected int $personResident;

    protected int $personB;

    protected int $personShared;

    protected function setUp(): void
    {
        parent::setUp();

        $this->devA = $this->createDevelopment('Development A');
        $this->devB = $this->createDevelopment('Development B');

        $this->superUser = $this->createUser('super', null);
        $this->assignRole($this->superUser, 'super_administrator', null);
        $this->adminA = $this->createUser('admin_a', null);
        $this->assignRole($this->adminA, 'administrator', $this->devA);
        $this->adminB = $this->createUser('admin_b', null);
        $this->assignRole($this->adminB, 'administrator', $this->devB);
        $this->accountantA = $this->createUser('accountant_a', null);
        $this->assignRole($this->accountantA, 'accountant', $this->devA);
        $this->dataEntryA = $this->createUser('data_entry_a', null);
        $this->assignRole($this->dataEntryA, 'data_entry', $this->devA);
        $this->guardA = $this->createUser('guard_a', null);
        $this->assignRole($this->guardA, 'guard', $this->devA);
        $this->resident = $this->createUser('resident_a', null);
        $this->assignRole($this->resident, 'resident', $this->devA);
        $this->disabledAdmin = $this->createUser('disabled_admin', null, 'disabled');
        $this->assignRole($this->disabledAdmin, 'administrator', $this->devA);
        $this->endedAdmin = $this->createUser('ended_admin', null);
        $this->assignRole($this->endedAdmin, 'administrator', $this->devA, -30, -1);
        $this->futureAdmin = $this->createUser('future_admin', null);
        $this->assignRole($this->futureAdmin, 'administrator', $this->devA, 5);
        $this->loner = $this->createUser('loner', null);

        $this->personResident = $this->createPerson('Resident A', [$this->devA]);
        $this->personB = $this->createPerson('Person B', [$this->devB]);
        $this->personShared = $this->createPerson('Shared A and B', [$this->devA, $this->devB]);
        $this->db()->execute('UPDATE users SET person_id = ? WHERE id = ?', [$this->personResident, $this->resident]);
        $this->db()->execute('UPDATE users SET person_id = ? WHERE id = ?', [$this->personB, $this->adminB]);
    }

    /**
     * @param string $name Person name.
     * @param list<int> $developmentIds Developments the person is linked to.
     * @return int Person id.
     */
    protected function createPerson(string $name, array $developmentIds): int
    {
        $this->db()->execute(
            'INSERT INTO people (name, created, modified) VALUES (?, NOW(), NOW())',
            [$name],
        );
        $id = (int)$this->db()->execute('SELECT id FROM people WHERE name = ?', [$name])->fetch()[0];
        foreach ($developmentIds as $developmentId) {
            $this->db()->execute(
                'INSERT INTO person_developments (person_id, development_id) VALUES (?, ?)',
                [$id, $developmentId],
            );
        }

        return $id;
    }

    /**
     * Record a role-assignment event with the owner connection.
     *
     * @param int $actorId Actor.
     * @param int $developmentId Development.
     * @return int Event id.
     */
    protected function createAssignmentEvent(int $actorId, int $developmentId): int
    {
        return (int)$this->db()->execute(
            "INSERT INTO role_assignment_events
                (actor_user_id, role_assignment_id, development_id, action, after_snapshot, created)
             VALUES (?, 1, ?, 'granted', '{}'::jsonb, NOW()) RETURNING id",
            [$actorId, $developmentId],
        )->fetch()[0];
    }

    /**
     * Run a query as the runtime role and return the first column of every row, as sorted integers.
     *
     * @param string $sql Query.
     * @param list<mixed> $params Positional parameters.
     * @return list<int>
     */
    protected function ids(string $sql, array $params = []): array
    {
        $ids = array_map(
            static fn(array $row): int => (int)$row[0],
            $this->appDb()->execute($sql, $params)->fetchAll(),
        );
        sort($ids);

        return $ids;
    }

    /**
     * Run a statement as the runtime role inside a savepoint, so a rejection does not poison the session.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Positional parameters.
     * @return int|null Rows affected, or null when the database rejected the statement.
     */
    protected function attempt(string $sql, array $params = []): ?int
    {
        $connection = $this->appDb();
        $connection->execute('SAVEPOINT attempt');
        try {
            $count = $connection->execute($sql, $params)->rowCount();
            $connection->execute('RELEASE SAVEPOINT attempt');

            return $count;
        } catch (Throwable) {
            $connection->execute('ROLLBACK TO SAVEPOINT attempt');

            return null;
        }
    }

    /**
     * Run a statement as the runtime role inside a savepoint and report how the database answered.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Positional parameters.
     * @return string|null SQLSTATE of the error, or null when the statement succeeded.
     */
    protected function sqlStateOf(string $sql, array $params = []): ?string
    {
        $connection = $this->appDb();
        $connection->execute('SAVEPOINT attempt');
        try {
            $connection->execute($sql, $params)->fetchAll();
            $connection->execute('RELEASE SAVEPOINT attempt');

            return null;
        } catch (Throwable $e) {
            $connection->execute('ROLLBACK TO SAVEPOINT attempt');
            for (; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof PDOException && isset($e->errorInfo[0])) {
                    return (string)$e->errorInfo[0];
                }
            }

            return 'unknown';
        }
    }

    /**
     * The statement must be rejected, or match no row visible to the session.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Positional parameters.
     * @return void
     */
    protected function assertDenied(string $sql, array $params = []): void
    {
        $count = $this->attempt($sql, $params);
        $this->assertTrue($count === null || $count === 0, 'Expected the statement to be denied: ' . $sql);
    }

    /**
     * The statement must be rejected by the database (no privilege, or a policy violation), never silently skipped.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Positional parameters.
     * @return void
     */
    protected function assertRejected(string $sql, array $params = []): void
    {
        $this->assertNull($this->attempt($sql, $params), 'Expected the database to reject: ' . $sql);
    }

    /**
     * The statement must succeed and touch at least one row.
     *
     * @param string $sql Statement.
     * @param list<mixed> $params Positional parameters.
     * @return void
     */
    protected function assertAllowed(string $sql, array $params = []): void
    {
        $count = $this->attempt($sql, $params);
        $this->assertNotNull($count, 'The statement was rejected: ' . $sql);
        $this->assertGreaterThan(0, $count, 'The statement matched no row: ' . $sql);
    }

    /**
     * Rows the runtime role can read from a table, for the current session.
     *
     * @param string $table Table name.
     * @return list<int> Primary keys.
     */
    protected function visibleIds(string $table): array
    {
        return $this->ids("SELECT id FROM {$table}");
    }
}
