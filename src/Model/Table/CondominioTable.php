<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Condominio Model
 *
 * @method \App\Model\Entity\Condominio get($primaryKey, $options = [])
 * @method \App\Model\Entity\Condominio newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Condominio[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Condominio|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Condominio|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Condominio patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Condominio[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Condominio findOrCreate($search, callable $callback = null, $options = [])
 */
class CondominioTable extends Table
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

        $this->setTable('condominio');
        $this->setDisplayField('id_condominio');
        $this->setPrimaryKey('id_condominio');

        $this->belongsToMany('users',[
          'targetForeingKey'=>'id_user',
          'foreignKey'=>'id_condominio',
          'joinTable'=>'users_condominio',
        ]);

       /* $this->belongsToMany('amenidad',[
            'targetForeingKey'=>'id_amenidad',
            'foreignKey'=>'id_condominio',
            'joinTable'=>'amenidad_condominio',
        ]); */

        $this->addBehavior('Josegonzalez/Upload.Upload', [
            'logo'=> []
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
            ->integer('id_condominio')
            ->allowEmpty('id_condominio', 'create');

        $validator
            ->scalar('nombre_condominio')
            ->maxLength('nombre_condominio', 100)
            ->requirePresence('nombre_condominio', 'create')
            ->notEmpty('nombre_condominio');

        $validator
            ->allowEmpty('logo');

        $validator
            ->scalar('direccion_condominio')
            ->maxLength('direccion_condominio', 100)
            ->requirePresence('direccion_condominio', 'create')
            ->notEmpty('direccion_condominio');

        $validator
            ->scalar('telefono_condominio')
            ->maxLength('telefono_condominio', 100)
            ->requirePresence('telefono_condominio', 'create')
            ->notEmpty('telefono_condominio');


        return $validator;
    }
}
