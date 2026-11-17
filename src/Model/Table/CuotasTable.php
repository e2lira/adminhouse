<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Cuotas Model
 *
 * @method \App\Model\Entity\Cuota get($primaryKey, $options = [])
 * @method \App\Model\Entity\Cuota newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Cuota[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Cuota|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Cuota|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Cuota patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Cuota[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Cuota findOrCreate($search, callable $callback = null, $options = [])
 */
class CuotasTable extends Table
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

        $this->setTable('cuotas');
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
            ->integer('id_condominio')
            ->allowEmpty('id_condominio');

        $validator
            ->numeric('cuota')
            ->requirePresence('cuota', 'create')
            ->notEmpty('cuota');

        $validator
            ->scalar('cuota_letra')
            ->maxLength('cuota_letra', 100)
            ->requirePresence('cuota_letra', 'create')
            ->notEmpty('cuota_letra');

        $validator
            ->requirePresence('id_tipo', 'create')
            ->notEmpty('id_tipo');

        return $validator;
    }
}
