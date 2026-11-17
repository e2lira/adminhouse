<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Bloque Model
 *
 * @method \App\Model\Entity\Bloque get($primaryKey, $options = [])
 * @method \App\Model\Entity\Bloque newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Bloque[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Bloque|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bloque saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Bloque patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Bloque[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Bloque findOrCreate($search, callable $callback = null, $options = [])
 */
class BloqueTable extends Table
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

        $this->setTable('bloque');
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
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');

        $validator
            ->integer('id_tipo')
            ->allowEmptyString('id_tipo');

        $validator
            ->numeric('cuota')
            ->allowEmptyString('cuota');

        $validator
            ->scalar('cuota_letra')
            ->maxLength('cuota_letra', 255)
            ->allowEmptyString('cuota_letra');

        $validator
            ->scalar('concepto')
            ->maxLength('concepto', 255)
            ->allowEmptyString('concepto');

        $validator
            ->dateTime('fecha_cargo')
            ->allowEmptyDateTime('fecha_cargo');

        $validator
            ->dateTime('fecha')
            ->allowEmptyDateTime('fecha');

        return $validator;
    }
}
