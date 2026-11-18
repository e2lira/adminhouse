<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Landing page after sign-in. Placeholder until the real dashboards exist.
 */
class HomeController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $this->set('username', (string)$identity?->get('username'));
    }
}
