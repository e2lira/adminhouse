<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SaldoCierre Model
 *
 * @method \App\Model\Entity\SaldoCierre get($primaryKey, $options = [])
 * @method \App\Model\Entity\SaldoCierre newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\SaldoCierre[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCierre|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SaldoCierre saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\SaldoCierre patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCierre[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\SaldoCierre findOrCreate($search, callable $callback = null, $options = [])
 */
class SaldoCierreTable extends Table
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

        $this->setTable('saldo_cierre');
        $this->setDisplayField('id_cierre');
        $this->setPrimaryKey('id_cierre');
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
            ->integer('id_cierre')
            ->allowEmptyString('id_cierre', null, 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->decimal('saldo')
            ->allowEmptyString('saldo');

        $validator
            ->date('fecha')
            ->allowEmptyDate('fecha');

        return $validator;
    }
}
