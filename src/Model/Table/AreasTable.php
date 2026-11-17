<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Areas Model
 *
 * @method \App\Model\Entity\Area get($primaryKey, $options = [])
 * @method \App\Model\Entity\Area newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Area[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Area|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Area saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Area patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Area[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Area findOrCreate($search, callable $callback = null, $options = [])
 */
class AreasTable extends Table
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

        $this->setTable('areas');
        $this->setDisplayField('id_area');
        $this->setPrimaryKey('id_area');

        $this->addBehavior('Josegonzalez/Upload.Upload', [
            'imagen'=> []
        ]);
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
            ->integer('id_area')
            ->allowEmptyString('id_area', null, 'create');

        $validator
            ->integer('id_supervision')
            ->allowEmptyString('id_supervision');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->scalar('concepto')
            ->maxLength('concepto', 95)
            ->requirePresence('concepto', 'create')
            ->notEmptyString('concepto');

        $validator
            ->scalar('estado')
            ->maxLength('estado', 45)
            ->allowEmptyString('estado');

        $validator
            ->scalar('observaciones')
            ->maxLength('observaciones', 45)
            ->allowEmptyString('observaciones');

        $validator
            ->dateTime('fecha')
            ->allowEmptyDateTime('fecha');

        return $validator;
    }
}
