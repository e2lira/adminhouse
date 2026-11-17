<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Indiviso Model
 *
 * @method \App\Model\Entity\Indiviso get($primaryKey, $options = [])
 * @method \App\Model\Entity\Indiviso newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Indiviso[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Indiviso|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Indiviso saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Indiviso patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Indiviso[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Indiviso findOrCreate($search, callable $callback = null, $options = [])
 */
class IndivisoTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('indiviso');
        $this->setDisplayField('id_cuotas');
        $this->setPrimaryKey('id_cuotas');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id_cuotas')
            ->allowEmptyString('id_cuotas', null, 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->scalar('id_condomino')
            ->maxLength('id_condomino', 45)
            ->allowEmptyString('id_condomino');

        $validator
            ->integer('id_tipo')
            ->allowEmptyString('id_tipo');

        $validator
            ->numeric('cuota')
            ->requirePresence('cuota', 'create')
            ->notEmptyString('cuota');

        $validator
            ->scalar('cuota_letra')
            ->maxLength('cuota_letra', 100)
            ->requirePresence('cuota_letra', 'create')
            ->notEmptyString('cuota_letra');

        $validator
            ->scalar('concepto')
            ->maxLength('concepto', 45)
            ->allowEmptyString('concepto');

        $validator
            ->dateTime('fecha_cargo')
            ->allowEmptyDateTime('fecha_cargo');

        return $validator;
    }
}
