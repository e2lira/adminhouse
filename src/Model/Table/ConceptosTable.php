<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Conceptos Model
 *
 * @method \App\Model\Entity\Concepto get($primaryKey, $options = [])
 * @method \App\Model\Entity\Concepto newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Concepto[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Concepto|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Concepto saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Concepto patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Concepto[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Concepto findOrCreate($search, callable $callback = null, $options = [])
 */
class ConceptosTable extends Table
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

        $this->setTable('conceptos');
        $this->setDisplayField('id_conceptos');
        $this->setPrimaryKey('id_conceptos');
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
            ->integer('id_conceptos')
            ->allowEmptyString('id_conceptos', null, 'create');

        $validator
            ->integer('id_gasto')
            ->allowEmptyString('id_gasto');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->scalar('concept')
            ->maxLength('concept', 45)
            ->allowEmptyString('concept');

        $validator
            ->scalar('gasto')
            ->maxLength('gasto', 45)
            ->allowEmptyString('gasto');

        $validator
            ->scalar('monto_letra')
            ->maxLength('monto_letra', 145)
            ->allowEmptyString('monto_letra');

        $validator
            ->date('fecha')
            ->allowEmptyDate('fecha');

        $validator
            ->dateTime('fecha_pago')
            ->allowEmptyDateTime('fecha_pago');

        $validator
            ->scalar('tipo')
            ->maxLength('tipo', 45)
            ->allowEmptyString('tipo');

        return $validator;
    }
}
