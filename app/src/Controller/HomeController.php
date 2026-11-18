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
        // The page only shows the caller's own username; every signed-in user may open it.
        $this->Authorization->skipAuthorization();

        $identity = $this->Authentication->getIdentity();
        $this->set('username', (string)$identity?->get('username'));
    }
}
