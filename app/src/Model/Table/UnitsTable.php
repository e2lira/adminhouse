<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Rule\ExistsIn;
use Cake\ORM\Rule\IsUnique;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Units of a development (apartments, houses, lots), under layer 1: reads and writes need the development scope, and
 * bulk statements are refused. The validation mirrors the database checks so a bad value is a field error, not a 500.
 *
 * Both rules run under the save scope (design AD4b, W-B): the number is unique within its section (units with no
 * section share one namespace per development, like `UNIQUE NULLS NOT DISTINCT`), and the section must belong to the
 * unit's own development (composite key `(development_id, section_id)`).
 */
class UnitsTable extends Table
{
    use ScopedBulkOperationsTrait;
    use RefusesArchivedDevelopmentTrait;
    use ScopedOperationTrait;

    /**
     * Longest number: the database check (`char_length(number) <= 50`) and the validation below must agree.
     */
    public const NUMBER_MAX = 50;

    /**
     * The unit types, as in the database check.
     *
     * @var list<string>
     */
    public const TYPES = ['apartment', 'house', 'lot'];

    /**
     * Smallest and largest values the columns hold once rounded: `numeric(12, 2)` for the lot area (a value below
     * 0.005 rounds to 0, which the `> 0` check refuses) and `numeric(7, 4)` for the indiviso, capped at 100.
     */
    private const AREA_MIN = 0.005;
    private const AREA_MAX = 9999999999.99;
    private const INDIVISO_MIN = 0.00005;
    private const INDIVISO_MAX = 100;

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('units');
        $this->setDisplayField('number');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('DevelopmentScoped');
        // Composite key: a unit can only point at a section of its own development, so the scope propagates (AD2).
        $this->belongsTo('Sections', [
            'foreignKey' => ['development_id', 'section_id'],
            'bindingKey' => ['development_id', 'id'],
            'joinType' => 'LEFT',
            'propertyName' => 'section',
        ]);
    }

    /**
     * Trims the number and the measurements; an empty or blank measurement or section becomes NULL ("no value" has
     * one representation).
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \ArrayObject<string, mixed> $data Request data.
     * @param \ArrayObject<string, mixed> $options Marshaller options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['number']) && is_string($data['number'])) {
            $data['number'] = trim($data['number']);
        }
        foreach (['lot_area_m2', 'indiviso_pct', 'section_id'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $trimmed = trim($data[$field]);
                $data[$field] = $trimmed === '' ? null : $trimmed;
            }
        }
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('number', 'create')
            ->scalar('number')
            ->maxLength('number', self::NUMBER_MAX)
            ->notEmptyString('number')
            ->notBlank('number');

        $validator
            ->requirePresence('type', 'create')
            ->scalar('type')
            ->inList('type', self::TYPES)
            ->notEmptyString('type');

        $validator
            ->allowEmptyString('section_id')
            ->integer('section_id')
            ->greaterThanOrEqual('section_id', 1);

        $this->measurement($validator, 'lot_area_m2', self::AREA_MIN, self::AREA_MAX);
        $this->measurement($validator, 'indiviso_pct', self::INDIVISO_MIN, self::INDIVISO_MAX);

        $validator->boolean('is_active')->notEmptyString('is_active');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        // Cake's default lets several NULLs through; here a NULL section is a value of its own.
        // The error belongs to the number, not to the first column of the key (`isUnique()` would pick that one).
        $rules->add(
            new IsUnique(['development_id', 'section_id', 'number'], ['allowMultipleNulls' => false]),
            '_isUnique',
            ['errorField' => 'number', 'message' => 'This number is already in use in this section.'],
        );

        $exists = new ExistsIn(['development_id', 'section_id'], 'Sections');
        $rules->add(
            fn(EntityInterface $unit, array $options): bool => $unit->get('section_id') === null
                || $exists($unit, $options),
            '_existsIn',
            ['errorField' => 'section_id', 'message' => 'The section does not exist in this development.'],
        );

        return $rules;
    }

    /**
     * An optional positive measurement that fits its column once rounded; `NaN` is not numeric, so it is refused.
     *
     * @param \Cake\Validation\Validator $validator Validator.
     * @param string $field Column.
     * @param float $min Smallest accepted value.
     * @param float $max Largest accepted value.
     * @return void
     */
    private function measurement(Validator $validator, string $field, float $min, float $max): void
    {
        $validator
            ->allowEmptyString($field)
            ->numeric($field)
            ->add($field, 'finite', [
                'rule' => fn(mixed $value): bool => !is_float($value) || is_finite($value),
                'message' => 'The value must be a finite number.',
            ])
            ->greaterThanOrEqual($field, $min)
            ->lessThanOrEqual($field, $max);
    }
}
