<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Rubro Model
 *
 * @method \App\Model\Entity\Rubro get($primaryKey, $options = [])
 * @method \App\Model\Entity\Rubro newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Rubro[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Rubro|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Rubro patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Rubro[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Rubro findOrCreate($search, callable $callback = null, $options = [])
 */
class RubroTable extends Table
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

        $this->setTable('rubro');
        $this->setDisplayField('id_rubro');
        $this->setPrimaryKey('id_rubro');
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
            ->integer('id_rubro')
            ->allowEmpty('id_rubro', 'create');

        $validator
            ->scalar('rubro')
            ->maxLength('rubro', 45)
            ->requirePresence('rubro', 'create')
            ->notEmpty('rubro');

        return $validator;
    }
}
