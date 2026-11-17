<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Pendientes Model
 *
 * @method \App\Model\Entity\Pendiente get($primaryKey, $options = [])
 * @method \App\Model\Entity\Pendiente newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Pendiente[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Pendiente|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Pendiente patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Pendiente[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Pendiente findOrCreate($search, callable $callback = null, $options = [])
 */
class PendientesTable extends Table
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

        $this->setTable('pendientes');
        $this->setDisplayField('id_pendientes');
        $this->setPrimaryKey('id_pendientes');
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
            ->integer('id_pendientes')
            ->allowEmpty('id_pendientes', 'create');

        $validator
            ->scalar('contenido')
            ->maxLength('contenido', 250)
            ->requirePresence('contenido', 'create')
            ->notEmpty('contenido');

        $validator
            ->dateTime('fecha')
            ->allowEmpty('fecha');

        return $validator;
    }
}
