<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * TipoVisita Model
 *
 * @method \App\Model\Entity\TipoVisitum get($primaryKey, $options = [])
 * @method \App\Model\Entity\TipoVisitum newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\TipoVisitum[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TipoVisitum|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TipoVisitum saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TipoVisitum patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TipoVisitum[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\TipoVisitum findOrCreate($search, callable $callback = null, $options = [])
 */
class TipoVisitaTable extends Table
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

        $this->setTable('tipo_visita');
        $this->setDisplayField('id_tipo');
        $this->setPrimaryKey('id_tipo');
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
            ->integer('id_tipo')
            ->allowEmptyString('id_tipo', null, 'create');

        $validator
            ->scalar('tipo_visita')
            ->maxLength('tipo_visita', 45)
            ->requirePresence('tipo_visita', 'create')
            ->notEmptyString('tipo_visita');

        return $validator;
    }
}
