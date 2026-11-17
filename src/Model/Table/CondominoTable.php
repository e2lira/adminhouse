<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Condomino Model
 *
 * @method \App\Model\Entity\Condomino get($primaryKey, $options = [])
 * @method \App\Model\Entity\Condomino newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Condomino[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Condomino|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Condomino|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Condomino patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Condomino[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Condomino findOrCreate($search, callable $callback = null, $options = [])
 */
class CondominoTable extends Table
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

        $this->setTable('condomino');
        $this->setDisplayField('id_condomino');
        $this->setPrimaryKey('id_condomino');
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
            ->integer('id_condomino')
            ->allowEmpty('id_condomino', 'create');

        $validator
            ->integer('id_condominio')
            ->requirePresence('id_condominio', 'create')
            ->notEmpty('id_condominio');

        $validator
            ->scalar('numero_departamento')
            ->maxLength('numero_departamento', 45)
            ->requirePresence('numero_departamento', 'create')
            ->notEmpty('numero_departamento');

        $validator
            ->scalar('residente_condominio')
            ->maxLength('residente_condominio', 150)
            ->requirePresence('residente_condominio', 'create')
            ->notEmpty('residente_condominio');

        $validator
            ->scalar('numero_contacto')
            ->maxLength('numero_contacto', 150)
            ->allowEmpty('numero_contacto');

        $validator
            ->scalar('saldo_inicial')
            ->maxLength('saldo_inicial', 150)
            ->allowEmpty('saldo_inicial');

        $validator
            ->scalar('correo_contacto')
            ->maxLength('correo_contacto', 150)
            ->allowEmpty('correo_contacto');

        $validator
            ->scalar('cuota')
            ->allowEmpty('cuota');

        $validator
            ->scalar('qr')
            ->maxLength('qr', 150)
            ->allowEmpty('qr');

        $validator
            ->scalar('nota')
            ->allowEmpty('nota');

        $validator
            ->scalar('arrendatario')
            ->maxLength('arrendatario', 45)
            ->allowEmpty('arrendatario');

        $validator
            ->scalar('telefono_arrendatario')
            ->maxLength('telefono_arrendatario', 45)
            ->allowEmpty('telefono_arrendatario');

        $validator
            ->scalar('marca_auto')
            ->maxLength('marca_auto', 45)
            ->allowEmpty('marca_auto');

        $validator
            ->scalar('modelo_auto')
            ->maxLength('modelo_auto', 45)
            ->allowEmpty('modelo_auto');

        $validator
            ->scalar('color_auto')
            ->maxLength('color_auto', 45)
            ->allowEmpty('color_auto');

        $validator
            ->scalar('placas_auto')
            ->maxLength('placas_auto', 45)
            ->allowEmpty('placas_auto');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {

        return $rules;
    }
}
