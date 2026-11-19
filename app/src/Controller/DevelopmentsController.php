<?php
declare(strict_types=1);

namespace App\Controller;

use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use App\Authorization\RoleAssignmentManager;
use App\Model\Entity\Development;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Policy\DevelopmentPolicy;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * Developments the signed-in user may see, and their management (add, edit, archive, unarchive).
 *
 * Management is `developments.manage`, held by no role: only the super-administrator. Edit, archive and unarchive
 * find the development through that scope, so any other user gets the same 404 as for a missing id (AC-MD-1); add has
 * no id and answers 403. There is no delete: a development is archived (its data stays).
 *
 * @property \App\Model\Table\DevelopmentsTable $Developments
 * @property \App\Controller\Component\DevelopmentScopeComponent $DevelopmentScope
 */
class DevelopmentsController extends AppController
{
    private const TYPES = ['condominium' => 'Condominio', 'subdivision' => 'Fraccionamiento'];
    private const DEFAULT_TIMEZONE = 'America/Mexico_City';

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
        // Fields are listed on purpose: bank, RFC and phone are readable by every role that sees the development at
        // the database level, so no screen of this list may load them.
        $query = $this->Developments->find()
            ->select(['Developments.id', 'Developments.name', 'Developments.type', 'Developments.archived_at'])
            ->orderBy(['Developments.name' => 'ASC']);

        // 403 when the user holds developments.view nowhere; otherwise only their developments are listed.
        $this->Authorization->authorize($query, 'index');
        $developments = $this->Authorization->applyScope($query, 'index')->all();

        // Links only where the viewer may use them (one query each, not one per row).
        $manageable = $this->DevelopmentScope->for(RoleAssignmentManager::PERMISSION);
        $managing = $this->DevelopmentScope->for(DevelopmentPolicy::MANAGE);

        $this->set(compact('developments', 'manageable', 'managing'));
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $this->Authorization->authorize($this->Developments, 'add');
        $development = $this->Developments->newEmptyEntity();

        if ($this->request->is('post')) {
            $development = $this->Developments->newEntity((array)$this->request->getData());
            if ($this->persist($development, $this->DevelopmentScope->for(DevelopmentPolicy::MANAGE))) {
                $this->Flash->success('Desarrollo creado.');

                return $this->redirect('/developments');
            }
            $this->Flash->error('No se pudo guardar el desarrollo.');
        } else {
            $development->timezone = self::DEFAULT_TIMEZONE;
        }

        return $this->showForm($development, 'Nuevo desarrollo', '/developments/add');
    }

    /**
     * @param string|int $id Development id from the URL.
     * @return \Cake\Http\Response|null
     */
    public function edit(int|string $id): ?Response
    {
        $development = $this->managedDevelopment($id, 'edit');

        if ($this->request->is('post')) {
            $this->Developments->patchEntity($development, (array)$this->request->getData());
            if ($this->persist($development, $this->DevelopmentScope->for(DevelopmentPolicy::MANAGE))) {
                $this->Flash->success('Desarrollo actualizado.');

                return $this->redirect('/developments');
            }
            $this->Flash->error('No se pudo guardar el desarrollo.');
        }

        return $this->showForm($development, 'Editar desarrollo', '/developments/' . (int)$development->id . '/edit');
    }

    /**
     * @param string|int $id Development id from the URL.
     * @return \Cake\Http\Response
     */
    public function archive(int|string $id): Response
    {
        return $this->changeArchiveState($id, true);
    }

    /**
     * @param string|int $id Development id from the URL.
     * @return \Cake\Http\Response
     */
    public function unarchive(int|string $id): Response
    {
        return $this->changeArchiveState($id, false);
    }

    /**
     * @param string|int $id Development id from the URL.
     * @param bool $archive True to archive, false to restore.
     * @return \Cake\Http\Response
     */
    private function changeArchiveState(int|string $id, bool $archive): Response
    {
        $this->request->allowMethod(['post']);
        // Archiving and restoring are the same ability: whoever may archive may unarchive (super-administrator only).
        // The row is read FOR UPDATE inside the request transaction: a concurrent request that changes the same
        // development waits here, then sees the new state and takes the no-op branch below. That is the conditional
        // transition (archive only when active, restore only when archived) without a bulk UPDATE, which the scoped
        // table refuses; the write itself stays an entity save under the scope.
        $development = $this->managedDevelopment($id, 'archive', lock: true);

        if (($development->archived_at !== null) === $archive) {
            $this->Flash->warning($archive ? 'El desarrollo ya estaba archivado.' : 'El desarrollo no está archivado.');

            return $this->redirect('/developments');
        }

        // Set explicitly: archived_at is not mass-assignable.
        $development->archived_at = $archive ? DateTime::now() : null;
        if ($this->persist($development, $this->DevelopmentScope->for(DevelopmentPolicy::MANAGE))) {
            $this->Flash->success($archive ? 'Desarrollo archivado.' : 'Desarrollo restaurado.');
        } else {
            $this->Flash->error(
                $archive ? 'No se pudo archivar el desarrollo.' : 'No se pudo restaurar el desarrollo.',
            );
        }

        return $this->redirect('/developments');
    }

    /**
     * Load the development under the manage scope and authorize; every refusal is the same 404.
     *
     * @param string|int $id Development id from the URL.
     * @param string $ability Policy ability (`edit` or `archive`).
     * @param bool $lock Whether to lock the row until the end of the transaction (`FOR UPDATE`).
     * @return \App\Model\Entity\Development
     */
    private function managedDevelopment(int|string $id, string $ability, bool $lock = false): Development
    {
        $scope = $this->DevelopmentScope->for(DevelopmentPolicy::MANAGE);
        $developmentId = $this->DevelopmentScope->development($id, DevelopmentPolicy::MANAGE);
        $query = $this->Developments->find('all', developmentScope: $scope)
            ->where(['Developments.id' => $developmentId]);
        if ($lock) {
            $query->epilog('FOR UPDATE');
        }
        /** @var \App\Model\Entity\Development $development */
        $development = $query->firstOrFail();
        $this->Authorization->authorize($development, $ability);

        return $development;
    }

    /**
     * Save under the scope and report whether the row was really written: a `false` result and a refused or
     * zero-row scoped write both count as failure, never as success.
     *
     * @param \App\Model\Entity\Development $development Development.
     * @param \App\Authorization\DevelopmentScope $scope Scope the write runs under.
     * @return bool
     */
    private function persist(Development $development, DevelopmentScope $scope): bool
    {
        try {
            return DevelopmentScopeStack::run($scope, fn() => $this->Developments->save($development)) !== false;
        } catch (DevelopmentScopeViolation) {
            return false;
        }
    }

    /**
     * @param \App\Model\Entity\Development $development Development to show.
     * @param string $title Page title.
     * @param string $url Where the form posts.
     * @return \Cake\Http\Response
     */
    private function showForm(Development $development, string $title, string $url): Response
    {
        $types = self::TYPES;
        $this->set(compact('development', 'title', 'url', 'types'));

        return $this->render('form');
    }
}
