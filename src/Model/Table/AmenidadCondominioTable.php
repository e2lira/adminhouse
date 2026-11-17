<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AmenidadCondominio Model
 *
 * @method \App\Model\Entity\AmenidadCondominio get($primaryKey, $options = [])
 * @method \App\Model\Entity\AmenidadCondominio newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\AmenidadCondominio[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AmenidadCondominio|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AmenidadCondominio patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AmenidadCondominio[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\AmenidadCondominio findOrCreate($search, callable $callback = null, $options = [])
 */
class AmenidadCondominioTable extends Table
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

        $this->setTable('amenidad_condominio');
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
            ->allowEmpty('id', 'create');

        $validator
            ->integer('id_amenidad')
            ->requirePresence('id_amenidad', 'create')
            ->notEmpty('id_amenidad');

        $validator
            ->scalar('id_condominio')
            ->maxLength('id_condominio', 45)
            ->requirePresence('id_condominio', 'create')
            ->notEmpty('id_condominio');

        return $validator;
    }
}
