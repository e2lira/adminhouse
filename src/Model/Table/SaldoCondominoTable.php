<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SaldoCondomino Model
 *
 * @method \App\Model\Entity\SaldoCondomino get($primaryKey, $options = [])
 * @method \App\Model\Entity\SaldoCondomino newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\SaldoCondomino[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCondomino|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SaldoCondomino saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SaldoCondomino patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCondomino[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCondomino findOrCreate($search, callable $callback = null, $options = [])
 */
class SaldoCondominoTable extends Table
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

        $this->setTable('saldo_condomino');
        $this->setDisplayField('id_saldo');
        $this->setPrimaryKey('id_saldo');
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
            ->integer('id_saldo')
            ->allowEmptyString('id_saldo', null, 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');


        return $validator;
    }
}
