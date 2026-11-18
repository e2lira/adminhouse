<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Developments the signed-in user may see.
 *
 * @property \App\Model\Table\DevelopmentsTable $Developments
 */
class DevelopmentsController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $query = $this->Developments->find()->orderBy(['Developments.name' => 'ASC']);

        // 403 when the user holds developments.view nowhere; otherwise only their developments are listed.
        $this->Authorization->authorize($query, 'index');
        $developments = $this->Authorization->applyScope($query, 'index')->all();

        $this->set(compact('developments'));
    }
}
