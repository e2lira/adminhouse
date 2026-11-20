<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Development;
use App\Model\Entity\Section;
use App\Model\Entity\Unit;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Policy\DevelopmentPolicy;
use App\Policy\UnitsAccess;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\ORM\Table;

/**
 * Units of a development and the sections that group them (created inline): list, open, add, edit, deactivate and
 * reactivate. There is no delete: a unit is deactivated, and no route reaches a section beyond creating it.
 *
 * Every action first resolves the development named in the URL under the scope the action needs, so a development
 * outside it answers the same bare 404 as one that does not exist (AC-MD-1): `units.view` to read, `units.manage` to
 * write. A write also authorizes the entity itself, carrying the development of the URL, before anything is saved
 * (the table policies only gate the interface), and saves under the manage scope, never the view scope. A row is
 * always looked up by its id AND the development of the URL.
 *
 * @property \App\Model\Table\UnitsTable $Units
 * @property \App\Controller\Component\DevelopmentScopeComponent $DevelopmentScope
 */
class UnitsController extends AppController
{
    private const TYPES = ['apartment' => 'Departamento', 'house' => 'Casa', 'lot' => 'Lote'];
    private const PAGE_SIZE = 100;
    private const DUPLICATE = 'Ya existe una unidad con ese valor de «{number}» para el mismo valor de '
        . '«{section}» (o sin él).';
    private const ARCHIVED = 'El desarrollo está archivado: no se pueden guardar cambios.';

    /**
     * Spanish copy for the field errors, by field and rule (the model keeps its messages in English). `{number}` and
     * `{section}` stand for the development's own labels, in lower case.
     */
    private const ERRORS = [
        'number' => [
            '_required' => 'Escribe un valor para «{number}».',
            '_empty' => 'Escribe un valor para «{number}».',
            'notBlank' => 'Escribe un valor para «{number}».',
            'maxLength' => 'No puede tener más de 50 caracteres.',
            '_isUnique' => self::DUPLICATE,
            '_unique' => self::DUPLICATE,
        ],
        'type' => ['*' => 'Elige un tipo válido.'],
        'section_id' => [
            '_existsIn' => 'El valor de «{section}» no existe en este desarrollo.',
            '*' => 'Elige un valor válido para «{section}».',
        ],
        'lot_area_m2' => [
            'numeric' => 'El valor debe ser un número.',
            'finite' => 'El valor debe ser un número.',
            'greaterThanOrEqual' => 'El valor debe ser mayor que cero.',
            'lessThanOrEqual' => 'El valor es demasiado grande.',
        ],
        'indiviso_pct' => [
            'numeric' => 'El valor debe ser un número.',
            'finite' => 'El valor debe ser un número.',
            'greaterThanOrEqual' => 'El valor debe ser mayor que cero.',
            'lessThanOrEqual' => 'El valor no puede ser mayor que 100.',
        ],
    ];

    /**
     * Why a section could not be saved, by rule, for the message after the redirect.
     */
    private const SECTION_ERRORS = [
        '_isUnique' => 'ya existe una con ese nombre.',
        '_unique' => 'ya existe una con ese nombre.',
        'maxLength' => 'el nombre no puede tener más de 150 caracteres.',
    ];

    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('DevelopmentScope');
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @return void
     */
    public function index(int|string $developmentId): void
    {
        $development = $this->readableDevelopment($developmentId);
        $query = $this->Units->find('all', developmentScope: $this->DevelopmentScope->for(UnitsAccess::VIEW))
            ->contain(['Sections'])
            ->where(['Units.development_id' => $development->id]);
        $units = $this->paginate($query, [
            'limit' => self::PAGE_SIZE,
            'maxLimit' => self::PAGE_SIZE,
            'order' => ['Sections.name' => 'ASC', 'Units.number' => 'ASC'],
            'sortableFields' => ['Sections.name', 'Units.number', 'Units.type'],
        ]);

        $this->set([
            'development' => $development,
            'units' => $units,
            'canManage' => $this->canWriteIn($development),
            'types' => self::TYPES,
        ]);
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string|int $id Unit id from the URL.
     * @return void
     */
    public function view(int|string $developmentId, int|string $id): void
    {
        $development = $this->readableDevelopment($developmentId);
        $unit = $this->unitOf($development, $id, contain: true);
        $this->Authorization->authorize($unit, 'view');

        $this->set([
            'development' => $development,
            'unit' => $unit,
            'canManage' => $this->canWriteIn($development),
            'types' => self::TYPES,
        ]);
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @return \Cake\Http\Response|null
     */
    public function add(int|string $developmentId): ?Response
    {
        $development = $this->writableDevelopment($developmentId);
        $unit = $this->Units->newEmptyEntity();
        $unit->development_id = (int)$development->id;
        $this->Authorization->authorize($unit, 'add');

        if ($this->request->is('post')) {
            $this->Units->patchEntity($unit, $this->unitData());
            if ($this->persist($this->Units, $unit, $development)) {
                $this->Flash->success('Unidad creada.');

                return $this->redirect($this->indexUrl($development));
            }
            $this->fail($unit, 'No se pudo guardar la unidad.', $development);
        }

        return $this->showForm($development, $unit, 'Nueva unidad');
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string|int $id Unit id from the URL.
     * @return \Cake\Http\Response|null
     */
    public function edit(int|string $developmentId, int|string $id): ?Response
    {
        $development = $this->readableDevelopment($developmentId);
        $unit = $this->unitOf($development, $id);
        $this->Authorization->authorize($unit, 'edit');

        if ($this->request->is('post')) {
            $this->Units->patchEntity($unit, $this->unitData());
            if ($this->persist($this->Units, $unit, $development)) {
                $this->Flash->success('Unidad actualizada.');

                return $this->redirect($this->indexUrl($development));
            }
            $this->fail($unit, 'No se pudo guardar la unidad.', $development);
        }

        return $this->showForm($development, $unit, 'Editar unidad');
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string|int $id Unit id from the URL.
     * @return \Cake\Http\Response
     */
    public function deactivate(int|string $developmentId, int|string $id): Response
    {
        return $this->setActive($developmentId, $id, false);
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string|int $id Unit id from the URL.
     * @return \Cake\Http\Response
     */
    public function activate(int|string $developmentId, int|string $id): Response
    {
        return $this->setActive($developmentId, $id, true);
    }

    /**
     * Create a section from the unit screens. A refusal comes back as a message on the list.
     *
     * @param string|int $developmentId Development id from the URL.
     * @return \Cake\Http\Response
     */
    public function addSection(int|string $developmentId): Response
    {
        $this->request->allowMethod(['post']);
        $development = $this->writableDevelopment($developmentId);
        $sections = $this->fetchTable('Sections');
        /** @var \App\Model\Entity\Section $section */
        $section = $sections->newEmptyEntity();
        $section->development_id = (int)$development->id;
        $this->Authorization->authorize($section, 'add');

        $sections->patchEntity($section, ['name' => $this->request->getData('name')]);
        if ($this->persist($sections, $section, $development)) {
            $this->Flash->success('Sección creada.');
        } elseif ($section->getError('development_id') !== []) {
            $this->Flash->error(self::ARCHIVED);
        } else {
            $this->Flash->error('No se pudo guardar la sección: ' . $this->sectionProblem($section));
        }

        return $this->redirect($this->indexUrl($development));
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string|int $id Unit id from the URL.
     * @param bool $active The state to leave the unit in.
     * @return \Cake\Http\Response
     */
    private function setActive(int|string $developmentId, int|string $id, bool $active): Response
    {
        $this->request->allowMethod(['post']);
        $development = $this->readableDevelopment($developmentId);
        $unit = $this->unitOf($development, $id);
        $this->Authorization->authorize($unit, 'edit');

        if ($unit->is_active === $active) {
            $this->Flash->warning($active ? 'La unidad ya estaba activa.' : 'La unidad ya estaba inactiva.');
        } else {
            // Set explicitly: it is the one change this action makes, whatever the request carries.
            $unit->is_active = $active;
            if ($this->persist($this->Units, $unit, $development)) {
                $this->Flash->success($active ? 'Unidad reactivada.' : 'Unidad desactivada.');
            } else {
                $this->Flash->error(
                    $unit->getError('development_id') !== []
                        ? self::ARCHIVED
                        : 'No se pudo cambiar el estado de la unidad.',
                );
            }
        }

        return $this->redirect($this->indexUrl($development));
    }

    /**
     * To read: the user must hold `units.view` somewhere (otherwise 403), and in this development (otherwise 404).
     *
     * @param string|int $developmentId Development id from the URL.
     * @return \App\Model\Entity\Development
     */
    private function readableDevelopment(int|string $developmentId): Development
    {
        $this->Authorization->authorize($this->Units, 'index');

        return $this->developmentUnder($developmentId, UnitsAccess::VIEW);
    }

    /**
     * To write: the user must hold `units.manage` somewhere (otherwise 403), and in this development (otherwise 404).
     * The entity policy still has to allow the row (archived developments refuse).
     *
     * @param string|int $developmentId Development id from the URL.
     * @return \App\Model\Entity\Development
     */
    private function writableDevelopment(int|string $developmentId): Development
    {
        $this->Authorization->authorize($this->Units, 'add');

        return $this->developmentUnder($developmentId, DevelopmentPolicy::UNITS_MANAGE);
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string $permission Permission the action needs.
     * @return \App\Model\Entity\Development Only what the screens show (never the bank or contact fields).
     */
    private function developmentUnder(int|string $developmentId, string $permission): Development
    {
        $id = $this->DevelopmentScope->development($developmentId, $permission);
        /** @var \App\Model\Entity\Development */
        return $this->fetchTable('Developments')
            ->find('all', developmentScope: $this->DevelopmentScope->for($permission))
            ->select([
                'Developments.id',
                'Developments.name',
                'Developments.section_label',
                'Developments.number_label',
            ])
            ->where(['Developments.id' => $id])
            ->firstOrFail();
    }

    /**
     * A unit of the development of the URL, read under the view scope.
     *
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @param string|int $id Unit id from the URL.
     * @param bool $contain Whether to load its section.
     * @return \App\Model\Entity\Unit
     */
    private function unitOf(Development $development, int|string $id, bool $contain = false): Unit
    {
        $query = $this->Units->find('all', developmentScope: $this->DevelopmentScope->for(UnitsAccess::VIEW))
            ->where([
                'Units.id' => $this->DevelopmentScope->recordId($id),
                'Units.development_id' => $development->id,
            ]);
        if ($contain) {
            $query->contain(['Sections']);
        }
        /** @var \App\Model\Entity\Unit */
        return $query->firstOrFail();
    }

    /**
     * Whether the user may add and change units here (to show or hide the write links); the policy decides.
     *
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @return bool
     */
    private function canWriteIn(Development $development): bool
    {
        $probe = $this->Units->newEmptyEntity();
        $probe->development_id = (int)$development->id;

        return $this->Authorization->can($probe, 'add');
    }

    /**
     * The fields a form may send; the development, the key and the active flag are never taken from the request.
     *
     * @return array<string, mixed>
     */
    private function unitData(): array
    {
        return array_intersect_key(
            (array)$this->request->getData(),
            array_flip(['number', 'type', 'section_id', 'lot_area_m2', 'indiviso_pct']),
        );
    }

    /**
     * Save under the manage scope, for the development of the URL, and report whether the row was written.
     *
     * @param \Cake\ORM\Table $table Units or sections.
     * @param \Cake\Datasource\EntityInterface $entity Entity, already authorized.
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @return bool
     */
    private function persist(Table $table, EntityInterface $entity, Development $development): bool
    {
        try {
            return $table->save($entity, [
                'developmentScope' => $this->DevelopmentScope->for(DevelopmentPolicy::UNITS_MANAGE),
                'developmentId' => (int)$development->id,
            ]) !== false;
        } catch (DevelopmentScopeViolation) {
            return false;
        }
    }

    /**
     * Say why a unit was not saved: the archived development is a message of its own, anything else is shown on the
     * fields, in Spanish.
     *
     * @param \App\Model\Entity\Unit $unit Unit with errors.
     * @param string $message Message when the fields explain the failure.
     * @param \App\Model\Entity\Development $development Development whose labels the messages use.
     * @return void
     */
    private function fail(Unit $unit, string $message, Development $development): void
    {
        if ($unit->getError('development_id') !== []) {
            $this->Flash->error(self::ARCHIVED);

            return;
        }
        foreach ($unit->getErrors() as $field => $rules) {
            $copy = self::ERRORS[$field] ?? [];
            $translated = [];
            foreach (array_keys((array)$rules) as $rule) {
                $translated[$rule] = strtr($copy[$rule] ?? $copy['*'] ?? 'El valor no es válido.', [
                    '{number}' => mb_strtolower((string)$development->number_label),
                    '{section}' => mb_strtolower((string)$development->section_label),
                ]);
            }
            $unit->setError($field, $translated, true);
        }
        $this->Flash->error($message);
    }

    /**
     * @param \App\Model\Entity\Section $section Section that failed to save.
     * @return string
     */
    private function sectionProblem(Section $section): string
    {
        foreach ((array)$section->getError('name') as $rule => $unused) {
            if (isset(self::SECTION_ERRORS[$rule])) {
                return self::SECTION_ERRORS[$rule];
            }
        }

        return $section->getErrors() === [] ? 'inténtalo de nuevo.' : 'escribe un nombre.';
    }

    /**
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @return string
     */
    private function indexUrl(Development $development): string
    {
        return '/developments/' . (int)$development->id . '/units';
    }

    /**
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @param \App\Model\Entity\Unit $unit Unit to show.
     * @param string $title Page title.
     * @return \Cake\Http\Response
     */
    private function showForm(Development $development, Unit $unit, string $title): Response
    {
        $sections = $this->fetchTable('Sections')
            ->find('all', developmentScope: $this->DevelopmentScope->for(UnitsAccess::VIEW))
            ->select(['Sections.id', 'Sections.name'])
            ->where(['Sections.development_id' => $development->id])
            ->orderBy(['Sections.name' => 'ASC'])
            ->all()
            ->combine('id', 'name')
            ->toArray();
        $url = $unit->isNew()
            ? $this->indexUrl($development) . '/add'
            : $this->indexUrl($development) . '/' . (int)$unit->id . '/edit';
        $types = self::TYPES;
        $this->set(compact('development', 'unit', 'title', 'url', 'sections', 'types'));

        return $this->render('form');
    }
}
