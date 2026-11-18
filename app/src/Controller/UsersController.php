<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Password sign-in and sign-out.
 */
class UsersController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event Event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login']);
    }

    /**
     * Show the sign-in form and handle its submission.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $result = $this->Authentication->getResult();

        if ($result !== null && $result->isValid()) {
            return $this->redirect($this->Authentication->getLoginRedirect() ?? '/');
        }

        if ($this->request->is('post')) {
            $this->Flash->error('Usuario o contraseña incorrectos.');
        }

        return null;
    }

    /**
     * End the session. Only POST is accepted so a link or image cannot sign the user out.
     *
     * @return \Cake\Http\Response
     */
    public function logout(): Response
    {
        $this->request->allowMethod(['post']);

        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
    }
}
