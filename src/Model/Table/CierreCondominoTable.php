<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CierreCondomino Model
 *
 * @method \App\Model\Entity\CierreCondomino get($primaryKey, $options = [])
 * @method \App\Model\Entity\CierreCondomino newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\CierreCondomino[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CierreCondomino|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CierreCondomino saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CierreCondomino patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CierreCondomino[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\CierreCondomino findOrCreate($search, callable $callback = null, $options = [])
 */
class CierreCondominoTable extends Table
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

        $this->setTable('cierre_condomino');
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
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');

        $validator
            ->scalar('saldo')
            ->maxLength('saldo', 45)
            ->allowEmptyString('saldo');

        $validator
            ->scalar('fecha')
            ->maxLength('fecha', 45)
            ->allowEmptyString('fecha');

        return $validator;
    }
}
