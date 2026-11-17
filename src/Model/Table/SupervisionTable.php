<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Supervision Model
 *
 * @method \App\Model\Entity\Supervision get($primaryKey, $options = [])
 * @method \App\Model\Entity\Supervision newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Supervision[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Supervision|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Supervision saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Supervision patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Supervision[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Supervision findOrCreate($search, callable $callback = null, $options = [])
 */
class SupervisionTable extends Table
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

        $this->setTable('supervision');
        $this->setDisplayField('id_supervision');
        $this->setPrimaryKey('id_supervision');
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
            ->nonNegativeInteger('id_supervision')
            ->allowEmptyString('id_supervision', null, 'create');

        $validator
            ->integer('id_condominio')
            ->requirePresence('id_condominio', 'create')
            ->notEmptyString('id_condominio');

        $validator
            ->dateTime('fecha')
            ->notEmptyDateTime('fecha');

        return $validator;
    }
}
