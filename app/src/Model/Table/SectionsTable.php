<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Sections of a development (towers, blocks, streets), under layer 1: reads and writes need the development scope,
 * and bulk statements are refused. The name is unique within a development, like the database key.
 */
class SectionsTable extends Table
{
    use ScopedBulkOperationsTrait;
    use RefusesArchivedDevelopmentTrait;
    use ScopedOperationTrait;

    /**
     * Longest name: the database check (`char_length(name) <= 150`) and the validation below must agree.
     */
    public const NAME_MAX = 150;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('sections');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('DevelopmentScoped');
    }

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \ArrayObject<string, mixed> $data Request data.
     * @param \ArrayObject<string, mixed> $options Marshaller options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
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
            ->maxLength('name', self::NAME_MAX)
            ->notEmptyString('name')
            ->notBlank('name');

        return $validator;
    }

    /**
     * `IsUnique` runs under the save scope (design AD4b): the development of a new section is already set.
     *
     * @param \Cake\ORM\RulesChecker $rules Rules.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        // The error belongs to the name, not to the first column of the key (`isUnique()` would pick that one).
        $rules->add(new IsUnique(['development_id', 'name']), '_isUnique', [
            'errorField' => 'name',
            'message' => 'This name is already in use in this development.',
        ]);

        return $rules;
    }
}
