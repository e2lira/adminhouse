<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Mensaje Model
 *
 * @method \App\Model\Entity\Mensaje get($primaryKey, $options = [])
 * @method \App\Model\Entity\Mensaje newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Mensaje[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Mensaje|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Mensaje saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Mensaje patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Mensaje[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Mensaje findOrCreate($search, callable $callback = null, $options = [])
 */
class MensajeTable extends Table
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

        $this->setTable('mensaje');
        $this->setDisplayField('id_mensaje');
        $this->setPrimaryKey('id_mensaje');


        $this->addBehavior('Josegonzalez/Upload.Upload', [
            'adjunto'=> []
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
            ->integer('id_mensaje')
            ->allowEmptyString('id_mensaje', null, 'create');

        $validator
            ->scalar('titulo')
            ->maxLength('titulo', 45)
            ->allowEmptyString('titulo');

        $validator
            ->scalar('mensaje')
            ->allowEmptyString('mensaje');


        $validator
            ->dateTime('fecha')
            ->allowEmptyDateTime('fecha');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->integer('id_condomino')
            ->allowEmptyString('id_condomino');

        $validator
            ->allowEmptyString('adjunto');

        return $validator;
    }
}
