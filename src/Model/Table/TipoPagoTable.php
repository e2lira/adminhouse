<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TipoPago Model
 *
 * @method \App\Model\Entity\TipoPago get($primaryKey, $options = [])
 * @method \App\Model\Entity\TipoPago newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\TipoPago[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TipoPago|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TipoPago|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TipoPago patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TipoPago[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\TipoPago findOrCreate($search, callable $callback = null, $options = [])
 */
class TipoPagoTable extends Table
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

        $this->setTable('tipo_pago');
        $this->setDisplayField('id_tipo');
        $this->setPrimaryKey('id_tipo');
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
            ->integer('id_tipo')
            ->allowEmpty('id_tipo', 'create');

        $validator
            ->scalar('nombre')
            ->maxLength('nombre', 50)
            ->allowEmpty('nombre');

        return $validator;
    }
}
