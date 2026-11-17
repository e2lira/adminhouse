<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Favor Model
 *
 * @method \App\Model\Entity\Favor get($primaryKey, $options = [])
 * @method \App\Model\Entity\Favor newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Favor[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Favor|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Favor saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Favor patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Favor[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Favor findOrCreate($search, callable $callback = null, $options = [])
 */
class FavorTable extends Table
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

        $this->setTable('favor');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('id_pago')
            ->allowEmptyString('id_pago');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');

        $validator
            ->numeric('abono')
            ->allowEmptyString('abono');

        $validator
            ->date('fecha')
            ->allowEmptyDate('fecha');

        $validator
            ->boolean('estatus')
            ->allowEmptyString('estatus');

        return $validator;
    }
}
