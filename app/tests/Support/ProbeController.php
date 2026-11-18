<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

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

        if ($this->request->getParam('action') === 'skipped') {
            $this->Authorization->skipAuthorization();
        }
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
