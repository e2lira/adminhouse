<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * UsuarioCondomino Model
 *
 * @method \App\Model\Entity\UsuarioCondomino get($primaryKey, $options = [])
 * @method \App\Model\Entity\UsuarioCondomino newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\UsuarioCondomino[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\UsuarioCondomino|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\UsuarioCondomino saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\UsuarioCondomino patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\UsuarioCondomino[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\UsuarioCondomino findOrCreate($search, callable $callback = null, $options = [])
 */
class UsuarioCondominoTable extends Table
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

        $this->setTable('usuario_condomino');
        $this->setDisplayField('id_pibote');
        $this->setPrimaryKey('id_pibote');
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
            ->integer('id_pibote')
            ->allowEmptyString('id_pibote', null, 'create');

        $validator
            ->integer('id')
            ->allowEmptyString('id');

        $validator
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');

        return $validator;
    }
}
