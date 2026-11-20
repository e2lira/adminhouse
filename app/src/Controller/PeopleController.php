<?php
declare(strict_types=1);

namespace App\Controller;

use App\Authorization\DevelopmentScope;
use App\Model\Entity\Development;
use App\Model\Entity\Person;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Policy\DevelopmentPolicy;
use App\Policy\PeopleAccess;
use Cake\Http\Response;

/**
 * The people of a development: list, add and edit. There is no delete, and no way to link an existing person to a
 * development (AD10): a person is created for one development, through `person_create`, and people shared between
 * developments come from the migration stage.
 *
 * Every action first resolves the development named in the URL under the scope the action needs, so a development
 * outside it answers the same bare 404 as one that does not exist (AC-MD-1): `people.view` to read, `people.manage` to
 * write. A person is always looked up by its id AND the development of the URL, under the view scope; that read is the
 * one every write starts from (task 11.8). A write authorizes the person for the development of the URL (an archived
 * development refuses) before anything is saved, and saves under the manage scope, never the view scope.
 *
 * Whether a person may be changed at all is decided by the database: a person shared with a development the editor
 * cannot manage (or that is archived) matches no row. The answer says so without saying why or with whom.
 *
 * @property \App\Model\Table\PeopleTable $People
 * @property \App\Controller\Component\DevelopmentScopeComponent $DevelopmentScope
 */
class PeopleController extends AppController
{
    use ResolvesDevelopmentTrait;

    private const PAGE_SIZE = 100;
    private const FIELDS = ['name', 'email', 'phone', 'rfc'];
    private const NOT_SAVED = 'No se pudo guardar a la persona.';
    private const NOT_CREATED = 'No se pudo crear a la persona en este desarrollo.';
    private const NOT_ALLOWED = 'No tienes permiso para modificar los datos de esta persona.';

    /**
     * Spanish copy for the field errors, by field and rule (the model keeps its messages in English).
     */
    private const ERRORS = [
        'name' => [
            'maxLength' => 'El nombre no puede tener más de 150 caracteres.',
            '*' => 'Escribe el nombre.',
        ],
        'email' => [
            'email' => 'Escribe un correo electrónico válido.',
            'maxLength' => 'El correo electrónico no puede tener más de 254 caracteres.',
        ],
        'phone' => ['maxLength' => 'El teléfono no puede tener más de 30 caracteres.'],
        'rfc' => ['maxLength' => 'El RFC no puede tener más de 13 caracteres.'],
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
        $query = $this->People->find('all', developmentScope: $this->scopeOf($development));
        $people = $this->paginate($query, [
            'limit' => self::PAGE_SIZE,
            'maxLimit' => self::PAGE_SIZE,
            'order' => ['People.name' => 'ASC', 'People.id' => 'ASC'],
            'sortableFields' => ['People.name', 'People.email'],
        ]);

        $this->set(['development' => $development, 'people' => $people, 'canManage' => $this->canWriteIn($development)]);
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @return \Cake\Http\Response|null
     */
    public function add(int|string $developmentId): ?Response
    {
        $this->Authorization->authorize($this->People, 'add');
        $development = $this->developmentUnder($developmentId, DevelopmentPolicy::PEOPLE_MANAGE);
        $person = $this->People->newEmptyEntity()->inDevelopment((int)$development->id);
        $this->Authorization->authorize($person, 'add');

        if ($this->request->is('post')) {
            try {
                $person = $this->People->createFor(
                    (int)$development->id,
                    $this->personData(),
                    $this->DevelopmentScope->for(DevelopmentPolicy::PEOPLE_MANAGE),
                );
            } catch (DevelopmentScopeViolation) {
                // The database refused (for example the development was archived a moment ago): one answer.
                $this->Flash->error(self::NOT_CREATED);
                $person = $this->People->newEntity($this->personData());

                return $this->showForm($development, $person, 'Nueva persona');
            }
            if ($person->getErrors() === []) {
                $this->Flash->success('Persona creada.');

                return $this->redirect($this->indexUrl($development));
            }
            $this->fail($person);
        }

        return $this->showForm($development, $person, 'Nueva persona');
    }

    /**
     * @param string|int $developmentId Development id from the URL.
     * @param string|int $id Person id from the URL.
     * @return \Cake\Http\Response|null
     */
    public function edit(int|string $developmentId, int|string $id): ?Response
    {
        $development = $this->readableDevelopment($developmentId);
        $person = $this->personOf($development, $id);
        $this->Authorization->authorize($person, 'edit');

        if ($this->request->is('post')) {
            $this->People->patchEntity($person, $this->personData());
            try {
                $saved = $this->People->save($person, [
                    'developmentScope' => $this->DevelopmentScope->for(DevelopmentPolicy::PEOPLE_MANAGE),
                ]);
            } catch (DevelopmentScopeViolation) {
                $this->Flash->error(self::NOT_ALLOWED);

                return $this->showForm($development, $person, 'Editar persona');
            }
            if ($saved !== false) {
                $this->Flash->success('Persona actualizada.');

                return $this->redirect($this->indexUrl($development));
            }
            $this->fail($person);
        }

        return $this->showForm($development, $person, 'Editar persona');
    }

    /**
     * To read: the user must hold `people.view` somewhere (otherwise 403, a resident among them), and in this
     * development (otherwise 404).
     *
     * @param string|int $developmentId Development id from the URL.
     * @return \App\Model\Entity\Development
     */
    private function readableDevelopment(int|string $developmentId): Development
    {
        $this->Authorization->authorize($this->People, 'index');

        return $this->developmentUnder($developmentId, PeopleAccess::VIEW);
    }

    /**
     * The development of the URL, inside what the user may see: a user who reads people in two developments is shown
     * the people of the one in the URL only.
     *
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @return \App\Authorization\DevelopmentScope
     */
    private function scopeOf(Development $development): DevelopmentScope
    {
        return DevelopmentScope::only([(int)$development->id])->intersect($this->DevelopmentScope->for(PeopleAccess::VIEW));
    }

    /**
     * A person of the development of the URL, read under the view scope: the read every write starts from.
     *
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @param string|int $id Person id from the URL.
     * @return \App\Model\Entity\Person Stated as handled in that development, for the policies.
     */
    private function personOf(Development $development, int|string $id): Person
    {
        /** @var \App\Model\Entity\Person $person */
        $person = $this->People->find('all', developmentScope: $this->scopeOf($development))
            ->where(['People.id' => $this->DevelopmentScope->recordId($id)])
            ->firstOrFail();

        return $person->inDevelopment((int)$development->id);
    }

    /**
     * Whether the user may add and change people here (to show or hide the write links); the policy decides.
     *
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @return bool
     */
    private function canWriteIn(Development $development): bool
    {
        return $this->Authorization->can($this->People->newEmptyEntity()->inDevelopment((int)$development->id), 'add');
    }

    /**
     * The fields a form may send; the key, the development and the timestamps are never taken from the request.
     *
     * @return array<string, mixed>
     */
    private function personData(): array
    {
        return array_intersect_key((array)$this->request->getData(), array_flip(self::FIELDS));
    }

    /**
     * Show why a person was not saved, in Spanish, on the fields.
     *
     * @param \App\Model\Entity\Person $person Person with errors.
     * @return void
     */
    private function fail(Person $person): void
    {
        foreach ($person->getErrors() as $field => $rules) {
            $copy = self::ERRORS[$field] ?? [];
            $translated = [];
            foreach (array_keys((array)$rules) as $rule) {
                $translated[$rule] = $copy[$rule] ?? $copy['*'] ?? 'El valor no es válido.';
            }
            $person->setError($field, $translated, true);
        }
        $this->Flash->error(self::NOT_SAVED);
    }

    /**
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @return string
     */
    private function indexUrl(Development $development): string
    {
        return '/developments/' . (int)$development->id . '/people';
    }

    /**
     * @param \App\Model\Entity\Development $development Development of the URL.
     * @param \App\Model\Entity\Person $person Person to show.
     * @param string $title Page title.
     * @return \Cake\Http\Response
     */
    private function showForm(Development $development, Person $person, string $title): Response
    {
        $url = $person->isNew()
            ? $this->indexUrl($development) . '/add'
            : $this->indexUrl($development) . '/' . (int)$person->id . '/edit';
        $this->set(compact('development', 'person', 'title', 'url'));

        return $this->render('form');
    }
}
