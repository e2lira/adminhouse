<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Comunicado Model
 *
 * @method \App\Model\Entity\Comunicado get($primaryKey, $options = [])
 * @method \App\Model\Entity\Comunicado newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Comunicado[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Comunicado|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Comunicado saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Comunicado patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Comunicado[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Comunicado findOrCreate($search, callable $callback = null, $options = [])
 */
class ComunicadoTable extends Table
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

        $this->setTable('comunicado');
        $this->setDisplayField('id_comunicado');
        $this->setPrimaryKey('id_comunicado');

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
            ->integer('id_comunicado')
            ->allowEmptyString('id_comunicado', null, 'create');

        $validator
            ->scalar('titulo')
            ->maxLength('titulo', 45)
            ->requirePresence('titulo', 'create')
            ->notEmptyString('titulo');

        $validator
            ->scalar('comunicado')
            ->requirePresence('comunicado', 'create')
            ->notEmptyString('comunicado');

        $validator
            ->integer('id_condominio')
            ->allowEmptyString('id_condominio');

        $validator
            ->allowEmptyString('adjunto');

        return $validator;
    }
}
