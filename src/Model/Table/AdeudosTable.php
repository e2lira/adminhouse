<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Adeudos Model
 *
 * @method \App\Model\Entity\Adeudo get($primaryKey, $options = [])
 * @method \App\Model\Entity\Adeudo newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Adeudo[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Adeudo|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Adeudo saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Adeudo patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Adeudo[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Adeudo findOrCreate($search, callable $callback = null, $options = [])
 */
class AdeudosTable extends Table
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

        $this->setTable('adeudos');
        $this->setDisplayField('id_adeudo');
        $this->setPrimaryKey('id_adeudo');
        $this->setAlias('T');

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
            ->integer('id_adeudo')
            ->allowEmptyString('id_adeudo', null, 'create');

        $validator
            ->integer('id_condominio')
            ->requirePresence('id_condominio', 'create')
            ->notEmptyString('id_condominio');


        $validator
            ->integer('monto')
            ->requirePresence('monto', 'create')
            ->notEmptyString('monto');

        $validator
            ->scalar('concepto')
            ->maxLength('concepto', 45)
            ->requirePresence('concepto', 'create')
            ->notEmptyString('concepto');

        return $validator;
    }
}
