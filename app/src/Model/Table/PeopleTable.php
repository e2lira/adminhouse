<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Authorization\DevelopmentScope;
use App\Authorization\DevelopmentScopeStack;
use App\Model\Entity\Person;
use App\Model\Exception\DevelopmentScopeViolation;
use App\Model\Exception\MissingDevelopmentScopeException;
use ArrayObject;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use LogicException;
use PDOException;
use Throwable;
use WeakMap;

/**
 * People, scoped through their development links (`person_developments`): a person is visible when it is linked to a
 * development of the scope. The behavior guards reads only, because a person has no development column to guard on a
 * write, so this table closes the gap itself (design AD3, AD8; the S2 carry-over of PR 1a-i, task 11.8):
 *
 * - A new person is never saved through the ORM: `people` has no INSERT grant, and `createFor()` calls the definer
 *   function `person_create`, which also creates the link, in one step.
 * - An existing person is saved only if THIS table read it under a scope (a person built by hand, or copied, was never
 *   read), its key was not changed, and it is still reachable under the scope of the save. The database then decides
 *   whether the editor may change it (a person shared with a development the editor cannot manage is refused, S-3).
 */
class PeopleTable extends Table
{
    use ScopedBulkOperationsTrait;
    use ScopedOperationTrait {
        save as private saveUnderScope;
    }

    /**
     * Limits of the optional fields. The database stores plain text, so these keep a form from storing a book.
     */
    private const NAME_MAX = 150;
    private const EMAIL_MAX = 254;
    private const PHONE_MAX = 30;
    private const RFC_MAX = 13;

    /**
     * The people this table has read, by object: a write proves it started from such a read.
     *
     * @var \WeakMap<\App\Model\Entity\Person, true>
     */
    private WeakMap $read;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->read = new WeakMap();
        $this->setTable('people');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->setEntityClass(Person::class);

        $this->addBehavior('Timestamp');
        $this->addBehavior('DevelopmentScoped', [
            'through' => 'person_developments',
            'throughKey' => 'person_id',
            'field' => 'development_id',
        ]);
    }

    /**
     * Remember the people each find returns (the scope itself is enforced by the behavior, which throws without one).
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array> $query Query.
     * @param \ArrayObject<string, mixed> $options Query options.
     * @param bool $primary Whether this is the main query.
     * @return void
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
        $query->formatResults(fn(CollectionInterface $results) => $results->map(function (mixed $row): mixed {
            if ($row instanceof Person) {
                $this->read[$row] = true;
            }

            return $row;
        }));
    }

    /**
     * Trims every field; a blank optional one becomes NULL ("no value" has one representation) and the RFC is kept in
     * capitals.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \ArrayObject<string, mixed> $data Request data.
     * @param \ArrayObject<string, mixed> $options Marshaller options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach (['name', 'email', 'phone', 'rfc'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $value = trim($data[$field]);
                $data[$field] = $field === 'name' || $value !== '' ? $value : null;
            }
        }
        if (isset($data['rfc']) && is_string($data['rfc']) && mb_check_encoding($data['rfc'], 'UTF-8')) {
            $data['rfc'] = mb_strtoupper($data['rfc']);
        }
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('name', 'create')
            ->scalar('name')
            ->notEmptyString('name')
            ->notBlank('name')
            ->maxLength('name', self::NAME_MAX);

        $validator->allowEmptyString('email')->email('email', false)->maxLength('email', self::EMAIL_MAX);
        $validator->allowEmptyString('phone')->maxLength('phone', self::PHONE_MAX);
        $validator->allowEmptyString('rfc')->maxLength('rfc', self::RFC_MAX);

        // Every field is one line of text: a control character (NUL, bell, a line break) or bytes that are not UTF-8
        // would be an error from the database, not a field error.
        foreach (['name', 'email', 'phone', 'rfc'] as $field) {
            $validator->add($field, 'noControl', [
                'rule' => fn(mixed $value): bool => !is_string($value)
                    || (mb_check_encoding($value, 'UTF-8') && preg_match('/\p{Cc}/u', $value) !== 1),
                'message' => 'The value contains characters that are not allowed.',
            ]);
        }

        return $validator;
    }

    /**
     * Nobody deletes a person through the application (the database grants it to the super-administrator only, for
     * maintenance, and no route reaches it).
     *
     * @param \Cake\Datasource\EntityInterface $entity Person.
     * @param array<string, mixed> $options Delete options.
     * @return bool
     * @throws \LogicException Always.
     */
    public function delete(EntityInterface $entity, array $options = []): bool
    {
        throw new LogicException('People are never deleted through the application.');
    }

    /**
     * Create a person in a development, through the definer function (the only way a person comes to exist).
     *
     * @param int $developmentId Development the person is linked to.
     * @param array<string, mixed> $data Request data; only the four editable fields are read.
     * @param \App\Authorization\DevelopmentScope $scope Developments the caller may write people in.
     * @return \App\Model\Entity\Person The stored person, read back under the scope; or the unsaved entity carrying
     *  the field errors when the data is not valid.
     * @throws \App\Model\Exception\DevelopmentScopeViolation When the development is outside the scope, or the
     *  database refuses the actor (not allowed, archived or unknown development: one answer for all of them).
     */
    public function createFor(int $developmentId, array $data, DevelopmentScope $scope): Person
    {
        /** @var \App\Model\Entity\Person $person */
        $person = $this->newEntity($data);
        if ($person->getErrors() !== []) {
            return $person;
        }
        if (!$scope->allows($developmentId)) {
            throw new DevelopmentScopeViolation();
        }

        try {
            $id = (int)$this->getConnection()->transactional(
                fn() => $this->getConnection()->execute(
                    'SELECT public.person_create(?, ?, ?, ?, ?)',
                    [$developmentId, $person->name, $person->email, $person->phone, $person->rfc],
                    ['integer', 'string', 'string', 'string', 'string'],
                )->fetch()[0],
            );
        } catch (Throwable $e) {
            for ($previous = $e; $previous !== null; $previous = $previous->getPrevious()) {
                if ($previous instanceof PDOException && ($previous->errorInfo[0] ?? null) === '42501') {
                    throw new DevelopmentScopeViolation();
                }
            }

            throw $e;
        }

        // The read-back needs `people.view`: a holder of `people.manage` always holds it too (the seeds are pinned
        // together by the `managedWritePairs` gate in AccessControlTest), and the creation is rolled back otherwise.
        /** @var \App\Model\Entity\Person */
        return $this->find('all', developmentScope: $scope)->where(['People.id' => $id])->first()
            ?? throw new DevelopmentScopeViolation();
    }

    /**
     * Only a person this table read, still reachable under the scope of the save, is written.
     *
     * @param \Cake\Datasource\EntityInterface $entity Person to save.
     * @param array<string, mixed> $options Save options; `developmentScope` is required.
     * @return \Cake\Datasource\EntityInterface|false
     * @throws \LogicException For a new person, or one this table never read.
     * @throws \App\Model\Exception\DevelopmentScopeViolation When the key changed or the scope does not reach it.
     * @throws \App\Model\Exception\MissingDevelopmentScopeException Without a scope.
     */
    public function save(EntityInterface $entity, array $options = []): EntityInterface|false
    {
        if ($entity->isNew()) {
            throw new LogicException('A person is never saved as a new entity: create it with createFor().');
        }
        $scope = $options['developmentScope'] ?? null;
        $running = DevelopmentScopeStack::current();
        $scope = $scope instanceof DevelopmentScope && $running !== null ? $running->intersect($scope) : $scope;
        if (!$scope instanceof DevelopmentScope) {
            throw new MissingDevelopmentScopeException($this->getAlias());
        }
        if (!$entity instanceof Person || !isset($this->read[$entity])) {
            throw new LogicException('A person must be read under a scope, through this table, before it is written.');
        }
        if (
            $entity->isDirty('id')
            || $this->find('all', developmentScope: $scope)->where(['People.id' => $entity->get('id')])->count() < 1
        ) {
            throw new DevelopmentScopeViolation();
        }

        return $this->saveUnderScope($entity, $options);
    }
}
