<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Bancaria Model
 *
 * @method \App\Model\Entity\Bancarium get($primaryKey, $options = [])
 * @method \App\Model\Entity\Bancarium newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Bancarium[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Bancarium|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bancarium patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Bancarium[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Bancarium findOrCreate($search, callable $callback = null, $options = [])
 */
class BancariaTable extends Table
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

        $this->setTable('bancaria');
        $this->setDisplayField('id_bancaria');
        $this->setPrimaryKey('id_bancaria');
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
            ->integer('id_bancaria')
            ->allowEmpty('id_bancaria', 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmpty('id_condominio');

        $validator
            ->scalar('nombre_banco')
            ->maxLength('nombre_banco', 55)
            ->requirePresence('nombre_banco', 'create')
            ->notEmpty('nombre_banco');

        $validator
            ->integer('no_cuenta')
            ->requirePresence('no_cuenta', 'create')
            ->notEmpty('no_cuenta');

        $validator
            ->scalar('estatus')
            ->maxLength('estatus', 45)
            ->requirePresence('estatus', 'create')
            ->notEmpty('estatus');

        return $validator;
    }
}
