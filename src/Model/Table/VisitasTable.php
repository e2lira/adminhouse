<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Visitas Model
 *
 * @method \App\Model\Entity\Visita get($primaryKey, $options = [])
 * @method \App\Model\Entity\Visita newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Visita[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Visita|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Visita saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Visita patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Visita[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Visita findOrCreate($search, callable $callback = null, $options = [])
 */
class VisitasTable extends Table
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

        $this->setTable('visitas');
        $this->setDisplayField('id_visita');
        $this->setPrimaryKey('id_visita');
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
            ->integer('id_visita')
            ->allowEmptyString('id_visita', null, 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');

        $validator
            ->scalar('nombre')
            ->maxLength('nombre', 255)
            ->requirePresence('nombre', 'create')
            ->notEmptyString('nombre');

        $validator
            ->scalar('departamento')
            ->maxLength('departamento', 45)
            ->allowEmptyString('departamento');

        $validator
            ->date('fecha')
            ->requirePresence('fecha', 'create')
            ->notEmptyDate('fecha');

        $validator
            ->time('hora')
            ->requirePresence('hora', 'create')
            ->notEmptyTime('hora');

        $validator
            ->scalar('tipo_visita')
            ->maxLength('tipo_visita', 45)
            ->requirePresence('tipo_visita', 'create')
            ->notEmptyString('tipo_visita');

        $validator
            ->scalar('auto')
            ->maxLength('auto', 45)
            ->allowEmptyString('auto');

        $validator
            ->scalar('color')
            ->maxLength('color', 45)
            ->allowEmptyString('color');

        $validator
            ->scalar('marca')
            ->maxLength('marca', 45)
            ->allowEmptyString('marca');

        $validator
            ->scalar('placas')
            ->maxLength('placas', 45)
            ->allowEmptyString('placas');

        return $validator;
    }
}
