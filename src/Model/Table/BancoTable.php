<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Banco Model
 *
 * @method \App\Model\Entity\Banco get($primaryKey, $options = [])
 * @method \App\Model\Entity\Banco newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Banco[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Banco|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Banco patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Banco[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Banco findOrCreate($search, callable $callback = null, $options = [])
 */
class BancoTable extends Table
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

        $this->setTable('banco');
        $this->setDisplayField('id_movimiento');
        $this->setPrimaryKey('id_movimiento');
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
            ->integer('id_movimiento')
            ->allowEmpty('id_movimiento', 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmpty('id_condominio');

        $validator
            ->integer('id_pago')
            ->allowEmpty('id_pago');

        $validator
            ->integer('id_cargo')
            ->allowEmpty('id_cargo');

        $validator
            ->numeric('cargo')
            ->allowEmpty('cargo');

        $validator
            ->numeric('abono')
            ->allowEmpty('abono');

        $validator
            ->dateTime('fecha')
            ->allowEmpty('fecha');

        $validator
            ->scalar('nombre_banco')
            ->maxLength('nombre_banco', 55)
            ->allowEmpty('nombre_banco');

        return $validator;
    }
}
