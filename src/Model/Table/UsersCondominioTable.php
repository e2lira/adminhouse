<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UsersCondominio Model
 *
 * @method \App\Model\Entity\UsersCondominio get($primaryKey, $options = [])
 * @method \App\Model\Entity\UsersCondominio newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\UsersCondominio[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UsersCondominio|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\UsersCondominio patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\UsersCondominio[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\UsersCondominio findOrCreate($search, callable $callback = null, $options = [])
 */
class UsersCondominioTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {

        $this->setTable('users_condominio');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey'=>'id_user'
        ]);
        $this->belongsTo('Condominio',[
            'foreignKey'=>'id_condominio'
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
            ->integer('id_user')
            ->requirePresence('id_user', 'create')
            ->notEmpty('id_user');

        $validator
            ->integer('id_condominio')
            ->requirePresence('id_condominio', 'create')
            ->notEmpty('id_condominio');

        return $validator;
    }
}
