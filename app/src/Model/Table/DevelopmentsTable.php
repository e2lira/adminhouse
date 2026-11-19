<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTimeZone;

/**
 * Developments (condominiums and subdivisions) administered in the system.
 *
 * Scoped by their own id (AD3): every read needs the `developmentScope` option, and bulk statements are refused.
 */
class DevelopmentsTable extends Table
{
    use ScopedBulkOperationsTrait;

    /**
     * Required text fields and their maximum length. 150 fits any realistic name or label ("Fraccionamiento Residencial
     * Lomas del Valle, Segunda Etapa") while keeping lists and forms readable.
     *
     * @var array<string, int>
     */
    private const REQUIRED_TEXT = ['name' => 150, 'section_label' => 150, 'number_label' => 150];

    /**
     * Optional profile fields and their maximum length: 255 for free addresses and bank names; 32 for phone (with
     * country code and extension), RFC (13 characters) and bank account (CLABE has 18 digits). Only a limit is
     * enforced: the spec leaves formats free.
     *
     * @var array<string, int>
     */
    private const OPTIONAL_TEXT = [
        'address' => 255, 'phone' => 32, 'rfc' => 32, 'bank_name' => 255, 'bank_account' => 32,
    ];

    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('developments');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('DevelopmentScoped', ['field' => 'id']);
    }

    /**
     * Trims every text field; an optional field that is empty or only whitespace becomes NULL, so "no value" has one
     * representation in the database.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event.
     * @param \ArrayObject<string, mixed> $data Request data.
     * @param \ArrayObject<string, mixed> $options Marshaller options.
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach ([...array_keys(self::REQUIRED_TEXT), ...array_keys(self::OPTIONAL_TEXT)] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }
        foreach (array_keys(self::OPTIONAL_TEXT) as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
    }

    /**
     * Type, name, labels and time zone are required; the five profile fields are optional and may be empty.
     *
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('type', 'create')
            ->inList('type', ['condominium', 'subdivision'])
            ->notEmptyString('type');

        foreach (self::REQUIRED_TEXT as $field => $max) {
            $validator
                ->requirePresence($field, 'create')
                ->scalar($field)
                ->maxLength($field, $max)
                ->notEmptyString($field)
                ->notBlank($field);
        }

        $validator
            ->requirePresence('timezone', 'create')
            ->inList('timezone', DateTimeZone::listIdentifiers())
            ->notEmptyString('timezone');

        foreach (self::OPTIONAL_TEXT as $field => $max) {
            $validator->scalar($field)->maxLength($field, $max)->allowEmptyString($field);
        }

        return $validator;
    }
}
