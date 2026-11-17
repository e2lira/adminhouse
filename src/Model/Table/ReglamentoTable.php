<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Reglamento Model
 *
 * @method \App\Model\Entity\Reglamento get($primaryKey, $options = [])
 * @method \App\Model\Entity\Reglamento newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Reglamento[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Reglamento|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Reglamento saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Reglamento patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Reglamento[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Reglamento findOrCreate($search, callable $callback = null, $options = [])
 */
class ReglamentoTable extends Table
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

        $this->setTable('reglamento');
        $this->setDisplayField('id_reglamento');
        $this->setPrimaryKey('id_reglamento');
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
            ->integer('id_reglamento')
            ->allowEmptyString('id_reglamento', null, 'create');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->scalar('nombre')
            ->maxLength('nombre', 45)
            ->allowEmptyString('nombre');

        $validator
            ->scalar('reglamento')
            ->allowEmptyString('reglamento');

        return $validator;
    }
}
