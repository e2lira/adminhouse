<?php
declare(strict_types=1);

namespace App\Model\Table;

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

        foreach (['name', 'section_label', 'number_label'] as $field) {
            $validator
                ->requirePresence($field, 'create')
                ->scalar($field)
                ->notEmptyString($field)
                ->notBlank($field);
        }

        $validator
            ->requirePresence('timezone', 'create')
            ->inList('timezone', DateTimeZone::listIdentifiers())
            ->notEmptyString('timezone');

        foreach (['address', 'phone', 'rfc', 'bank_name', 'bank_account'] as $field) {
            $validator->scalar($field)->allowEmptyString($field);
        }

        return $validator;
    }
}
