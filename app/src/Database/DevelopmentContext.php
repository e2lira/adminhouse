<?php
declare(strict_types=1);

namespace App\Database;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * Tells PostgreSQL who is acting, for row-level security (ADR-0005 section 1, layer 2).
 *
 * A request, a job or a command runs inside ONE transaction. Inside it the application sets, with
 * `set_config(name, value, true)` (transaction-local, bound parameters, never string interpolation), only:
 * - `app.context`: `request` or `job` (`webhook` arrives with the payments module);
 * - `app.user_id`: the signed-in user (requests);
 * - `app.job_dev_ids`: the developments a job may touch, comma separated (jobs).
 *
 * Everything else (which developments, whether the user is a super-administrator) is derived by the database from
 * the user's assignments on each transaction, so the application cannot claim rights by setting a value. Setting
 * `app.user_id` itself is the one lever the database cannot authenticate; authenticating that id is the job of the
 * application (sign-in, second factor, session).
 *
 * The values die with the transaction, so a pooled or reused connection never carries them to the next unit of work.
 * Savepoints are enabled so a unit of work nested inside the request (for example a service that catches a
 * constraint violation) can fail without aborting the request transaction.
 */
class DevelopmentContext
{
    public const REQUEST = 'request';
    public const JOB = 'job';

    /**
     * @param \Cake\Database\Connection|null $connection Connection to use; defaults to `default`.
     */
    public function __construct(private ?Connection $connection = null)
    {
    }

    /**
     * Open the transaction of a request. Without a user yet, as before authentication.
     *
     * @param int|null $userId Signed-in user, if already known.
     * @return void
     * @throws \LogicException When a transaction is already open on the connection.
     */
    public function beginRequest(?int $userId = null): void
    {
        $this->begin(self::REQUEST, $userId, []);
    }

    /**
     * Open the transaction of a job or command.
     *
     * @param list<int> $developmentIds Developments the job may touch; empty for none (for example a command that
     *  only creates accounts).
     * @return void
     * @throws \LogicException When a transaction is already open on the connection.
     * @throws \RuntimeException When the connection's role is not a constrained runtime role (RuntimeRoleGuard).
     */
    public function beginJob(array $developmentIds): void
    {
        RuntimeRoleGuard::assertSafe($this->connection());
        $this->begin(self::JOB, null, $developmentIds);
    }

    /**
     * Set the user of the request once authentication has identified them (null clears it).
     *
     * @param int|null $userId User id.
     * @return void
     */
    public function setUser(?int $userId): void
    {
        $this->connection()->execute(
            "SELECT set_config('app.user_id', ?, true)",
            [$userId === null ? '' : (string)$userId],
        );
    }

    /**
     * Commit the unit of work.
     *
     * PostgreSQL answers COMMIT on an aborted transaction with the tag ROLLBACK and no error, so a failed statement
     * that the application swallowed would look like a success. A probe statement first turns that state into an
     * error; the work is then rolled back and the failure is raised.
     *
     * @return void
     * @throws \RuntimeException When the transaction was aborted: nothing was committed.
     */
    public function commit(): void
    {
        $connection = $this->connection();
        if ($connection->inTransaction()) {
            try {
                $connection->execute('SELECT 1');
            } catch (Throwable $e) {
                $this->rollback();

                throw new RuntimeException(
                    'The database transaction was aborted and has been rolled back; nothing was committed.',
                    0,
                    $e,
                );
            }
        }
        while ($connection->inTransaction()) {
            $connection->commit();
        }
    }

    /**
     * @return void
     */
    public function rollback(): void
    {
        $connection = $this->connection();
        while ($connection->inTransaction()) {
            $connection->rollback();
        }
    }

    /**
     * Run a job: open its transaction, run the work, commit; roll back and rethrow when it fails.
     *
     * @template T
     * @param list<int> $developmentIds Developments the job may touch.
     * @param callable(): T $work The work.
     * @return T
     */
    public function runAsJob(array $developmentIds, callable $work): mixed
    {
        $this->beginJob($developmentIds);
        try {
            $result = $work();
        } catch (Throwable $e) {
            $this->rollback();

            throw $e;
        }
        $this->commit();

        return $result;
    }

    /**
     * @param string $context Context name.
     * @param int|null $userId User id.
     * @param list<int> $developmentIds Job developments.
     * @return void
     */
    private function begin(string $context, ?int $userId, array $developmentIds): void
    {
        $connection = $this->connection();
        if ($connection->inTransaction()) {
            throw new LogicException('A database transaction is already open; a unit of work opens only one.');
        }

        $connection->enableSavePoints(true);
        $connection->begin();
        try {
            $connection->execute(
                "SELECT set_config('app.context', ?, true), set_config('app.user_id', ?, true),
                        set_config('app.job_dev_ids', ?, true)",
                [
                    $context,
                    $userId === null ? '' : (string)$userId,
                    implode(',', array_map('intval', $developmentIds)),
                ],
            );
        } catch (Throwable $e) {
            $connection->rollback();

            throw $e;
        }
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function connection(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = $this->connection ??= ConnectionManager::get('default');

        return $connection;
    }
}
