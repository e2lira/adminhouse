<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use RuntimeException;

/**
 * Test-only controller: `unchecked` never authorizes, `skipped` opts out with skipAuthorization().
 * Lives in the App\Controller namespace so the dispatcher can resolve it; only loaded by tests.
 */
class ProbeController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event Event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $action = $this->request->getParam('action');
        if (in_array($action, ['skipped', 'session', 'write', 'writeThenFail'], true)) {
            $this->Authorization->skipAuthorization();
        }
        // The session probes are reachable anonymously so a request without a user can be observed.
        $this->Authentication->allowUnauthenticated(['session', 'write', 'writeThenFail']);
    }

    /**
     * Reports the session values PostgreSQL sees for this request (RLS-SEC-001).
     *
     * @return \Cake\Http\Response
     */
    public function session(): Response
    {
        $connection = ConnectionManager::get('default');
        $row = $connection->execute(
            "SELECT current_setting('app.context', true), current_setting('app.user_id', true), current_user",
        )->fetch();

        return $this->response->withType('json')->withStringBody((string)json_encode([
            'context' => $row[0],
            'user_id' => $row[1],
            'db_user' => $row[2],
            'in_transaction' => $connection->inTransaction(),
        ]));
    }

    /**
     * Writes one audit row through the runtime role and succeeds.
     *
     * @return \Cake\Http\Response
     */
    public function write(): Response
    {
        ConnectionManager::get('default')
            ->execute("SELECT auth_event_log(NULL, 'probe-write', 'password_ok', NULL, NULL)");

        return $this->response->withStringBody('written');
    }

    /**
     * Writes one audit row and then fails: the whole request must roll back.
     *
     * @return \Cake\Http\Response
     */
    public function writeThenFail(): Response
    {
        ConnectionManager::get('default')
            ->execute("SELECT auth_event_log(NULL, 'probe-fail', 'password_ok', NULL, NULL)");

        throw new RuntimeException('probe failure');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function unchecked(): Response
    {
        return $this->response->withStringBody('unchecked ran');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function skipped(): Response
    {
        return $this->response->withStringBody('skipped ok');
    }
}
