<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TipoCuota Model
 *
 * @method \App\Model\Entity\TipoCuotum get($primaryKey, $options = [])
 * @method \App\Model\Entity\TipoCuotum newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\TipoCuotum[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TipoCuotum|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TipoCuotum patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TipoCuotum[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\TipoCuotum findOrCreate($search, callable $callback = null, $options = [])
 */
class TipoCuotaTable extends Table
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

        $this->setTable('tipo_cuota');
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
            ->scalar('tipo_cuota')
            ->maxLength('tipo_cuota', 30)
            ->requirePresence('tipo_cuota', 'create')
            ->notEmpty('tipo_cuota');

        return $validator;
    }
}
