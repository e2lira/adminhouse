<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TipoGasto Model
 *
 * @method \App\Model\Entity\TipoGasto get($primaryKey, $options = [])
 * @method \App\Model\Entity\TipoGasto newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\TipoGasto[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TipoGasto|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TipoGasto patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TipoGasto[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\TipoGasto findOrCreate($search, callable $callback = null, $options = [])
 */
class TipoGastoTable extends Table
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

        $this->setTable('tipo_gasto');
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
            ->scalar('tipo_gasto')
            ->maxLength('tipo_gasto', 45)
            ->requirePresence('tipo_gasto', 'create')
            ->notEmpty('tipo_gasto');

        return $validator;
    }
}
