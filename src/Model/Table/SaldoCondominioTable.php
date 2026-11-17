<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SaldoCondominio Model
 *
 * @method \App\Model\Entity\SaldoCondominio get($primaryKey, $options = [])
 * @method \App\Model\Entity\SaldoCondominio newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\SaldoCondominio[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCondominio|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SaldoCondominio saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SaldoCondominio patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCondominio[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCondominio findOrCreate($search, callable $callback = null, $options = [])
 */
class SaldoCondominioTable extends Table
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

        $this->setTable('saldo_condominio');
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

        return $validator;
    }
}
