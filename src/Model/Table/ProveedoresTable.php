<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Proveedores Model
 *
 * @method \App\Model\Entity\Proveedore get($primaryKey, $options = [])
 * @method \App\Model\Entity\Proveedore newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Proveedore[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Proveedore|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Proveedore patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Proveedore[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Proveedore findOrCreate($search, callable $callback = null, $options = [])
 */
class ProveedoresTable extends Table
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

        $this->setTable('proveedores');
        $this->setDisplayField('id_proveedores');
        $this->setPrimaryKey('id_proveedores');
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
            ->integer('id_proveedores')
            ->allowEmpty('id_proveedores', 'create');

        $validator
            ->scalar('nom_proveedor')
            ->maxLength('nom_proveedor', 45)
            ->requirePresence('nom_proveedor', 'create')
            ->notEmpty('nom_proveedor');

        $validator
            ->allowEmpty('no_cuenta_proveedor');

        $validator
            ->scalar('rubro_proveedor')
            ->maxLength('rubro_proveedor', 45)
            ->allowEmpty('rubro_proveedor');

        $validator
            ->scalar('nota')
            ->allowEmpty('nota');

        $validator
        ->scalar('id_condominio')
        ->allowEmpty('id_condominio');

        return $validator;
    }

}
