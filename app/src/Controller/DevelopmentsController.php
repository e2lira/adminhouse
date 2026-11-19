<?php
declare(strict_types=1);

namespace App\Controller;

use App\Authorization\RoleAssignmentManager;

/**
 * Developments the signed-in user may see.
 *
 * @property \App\Model\Table\DevelopmentsTable $Developments
 * @property \App\Controller\Component\DevelopmentScopeComponent $DevelopmentScope
 */
class DevelopmentsController extends AppController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('DevelopmentScope');
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $query = $this->Developments->find()->orderBy(['Developments.name' => 'ASC']);

        // 403 when the user holds developments.view nowhere; otherwise only their developments are listed.
        $this->Authorization->authorize($query, 'index');
        $developments = $this->Authorization->applyScope($query, 'index')->all();

        // Link to the role screen only where the viewer may open it (one query, not one per row).
        $manageable = $this->DevelopmentScope->for(RoleAssignmentManager::PERMISSION);

        $this->set(compact('developments', 'manageable'));
    }
}
