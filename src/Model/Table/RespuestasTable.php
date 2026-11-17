<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Respuestas Model
 *
 * @method \App\Model\Entity\Respuesta get($primaryKey, $options = [])
 * @method \App\Model\Entity\Respuesta newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Respuesta[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Respuesta|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Respuesta saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Respuesta patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Respuesta[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Respuesta findOrCreate($search, callable $callback = null, $options = [])
 */
class RespuestasTable extends Table
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

        $this->setTable('respuestas');
        $this->setDisplayField('id_respuestas');
        $this->setPrimaryKey('id_respuestas');
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
            ->integer('id_respuestas')
            ->allowEmptyString('id_respuestas', null, 'create');

        $validator
            ->integer('id_mensaje')
            ->allowEmptyString('id_mensaje');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');

        return $validator;
    }
}
